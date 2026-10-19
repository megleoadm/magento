<?php

namespace Megleo\Delivery\Observer;

use Exception;
use Megleo\Delivery\Sdk\Client;
use Magento\Sales\Model\Order;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\CustomerFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\Order\Shipment;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface as Logger;
use stdClass;

class CreateShipmentQuote implements ObserverInterface
{
    const CODE = 'megleo_delivery';

    protected $_code = self::CODE;

    /**
     * Core store config
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * Loja do pedido sendo processado (o pedido pode ser criado pelo admin/API)
     *
     * @var int|null
     */
    private $storeId = null;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Order $order,
        CustomerFactory $customerFactory,
        QuoteFactory $quoteFactory,
        StoreManagerInterface $storeManager,
        Logger $logger
    ) {
        $this->_scopeConfig = $scopeConfig;

        $this->order = $order;
        $this->customerFactory = $customerFactory;
        $this->quoteFactory = $quoteFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if (!$order || !$order->getId() || strpos((string)$order->getShippingMethod(), self::CODE . '_') !== 0) {
            return;
        }

        // Pedido já registrado na Megleo
        if (!empty($order->getData('megleo_metadata'))) {
            return;
        }

        $this->storeId = $order->getStoreId();

        // Qualquer falha aqui não pode interromper a finalização do pedido
        try {
            $this->sendOrder($order, $observer->getEvent()->getQuote());
        } catch (\Throwable $e) {
            $message = 'Megleo: falha ao registrar o pedido: ' . $e->getMessage();

            $this->logger->critical($message, [
                'order' => $order->getIncrementId(),
                'exception' => $e,
            ]);

            try {
                $order->addCommentToStatusHistory($message);
                $order->save();
            } catch (\Throwable $ignored) {
            }
        }
    }

    private function sendOrder($order, $quote = null)
    {
        if (!$quote || !$quote->getId()) {
            $quote = $this->quoteFactory->create()->load($order->getQuoteId());
        }

        $shippingAddress = $order->getShippingAddress();

        $token  = $this->getConfigData('codigo_acesso');
        $apiUrl = $this->getConfigData('api_url');
        $client = new Client((string)$token, $apiUrl ? ['base_uri' => rtrim($apiUrl, '/') . '/'] : null);

        $criarPedido = $client->criarPedidoSimplificado();

        foreach ($order->getAllItems() as $item) {
            if ($item->getProduct()->isVirtual()) {
                continue;
            }

            $criarPedido->withProduct($this->getItemDetails($item));
        }

        $customer = $this->convertCustomer($order, $quote, $shippingAddress);

        $criarPedido
            ->withOrderId($order->getId())
            ->withCepOrigem($this->getFromZip($order))
            ->withCepDestino(preg_replace('/\D/', '', (string)$shippingAddress->getData('postcode')))
            ->withValorNota($order->getSubtotal())
            ->withCustomer($customer);

        $response = $criarPedido->criar();

        if (!empty($response->error)) {
            $error = is_scalar($response->error) ? $response->error : json_encode($response->error, JSON_UNESCAPED_UNICODE);
            throw new Exception('API retornou erro em pedidos/criar_simplificado: ' . $error);
        }

        $dataResponse = $response->data ?? [];
        if (!is_array($dataResponse)) {
            $dataResponse = [$dataResponse];
        }

        $metadata = [];
        foreach ($dataResponse as $result) {
            $data = new stdClass();

            $data->cnpj_pagador = $result->cnpj_pagador ?? null;
            $data->endereco_coleta = $result->endereco_coleta ?? null;
            $data->transportadora = $result->transportadora ?? null;
            $data->volumes = $result->volumes ?? null;
            $data->valor_fatura = $result->valor_fatura ?? null;

            $metadata[] = $data;
        }

        $order->setMegleoMetadata(json_encode($metadata, JSON_PRETTY_PRINT));
        $order->addCommentToStatusHistory('Megleo: pedido registrado com sucesso.');
        $order->save();
    }

    private function getTransportadoraId($quote)
    {
        $address = $quote->getShippingAddress();
        $address->collectShippingRates();

        foreach ($address->getAllShippingRates() as $rate) {
            return str_replace('ID ', '', $rate->getData('method_description'));
        }
    }

    private function getFromZip($order)
    {
        $fromZip = $this->_scopeConfig->getValue(
            Shipment::XML_PATH_STORE_ZIP,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );

        return preg_replace('/\D/', '', $fromZip);
    }

    private function getStoreAddress($order)
    {
        $store = new stdClass();

        $store->address1 = $this->getShipmentConfigData($order, Shipment::XML_PATH_STORE_ADDRESS1);
        $store->address2 = $this->getShipmentConfigData($order, Shipment::XML_PATH_STORE_ADDRESS2);
        $store->city = $this->getShipmentConfigData($order, Shipment::XML_PATH_STORE_CITY);
        $store->regionId = $this->getShipmentConfigData($order, Shipment::XML_PATH_STORE_REGION_ID);
        $store->zip = $this->getShipmentConfigData($order, Shipment::XML_PATH_STORE_ZIP);
        $store->country = $this->getShipmentConfigData($order, Shipment::XML_PATH_STORE_COUNTRY_ID);

        return $store;
    }

    private function convertShippingAddress($shippingAddress): stdClass
    {
        $address = new stdClass();

        $address->complemento = '';
        $address->numero = '';
        $address->bairro = '';
        $address->rua = $shippingAddress->getData('street');

        return $address;
    }

    private function convertCustomer($order, $quote, $shippingAddress): stdClass
    {
        $recipient  = new stdClass();

        [$cpf, $cnpj] = $this->findDocuments($order, $quote, $shippingAddress);

        if (empty($cpf) && empty($cnpj)) {
            throw new Exception('CPF/CNPJ do destinatário não encontrado no pedido nem no cadastro do cliente');
        }

        $recipient->tipo_pessoa = !empty($cnpj) ? 'pessoa_juridica' : 'pessoa_fisica';
        $recipient->cpf  = $cpf;
        $recipient->cnpj = $cnpj;

        $recipient->telefone = $shippingAddress->getTelephone();
        $recipient->nome  = trim($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname())
            ?: trim($shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname());
        $recipient->email = $order->getCustomerEmail();

        return $recipient;
    }

    /**
     * Procura CPF/CNPJ em todas as fontes possíveis, na ordem de prioridade.
     *
     * @return array [cpf, cnpj]
     */
    private function findDocuments($order, $quote, $shippingAddress): array
    {
        $sources = [$shippingAddress, $order->getBillingAddress()];

        if ($quote && $quote->getId()) {
            $sources[] = $quote->getShippingAddress();
            $sources[] = $quote->getBillingAddress();
        }

        if ($order->getCustomerId()) {
            $sources[] = $this->customerFactory->create()->load($order->getCustomerId());
        }

        foreach ($sources as $source) {
            if (!$source) {
                continue;
            }

            $cpf  = $this->onlyDigits($source->getData('cpf'));
            $cnpj = $this->onlyDigits($source->getData('cnpj'));

            $extAttributes = method_exists($source, 'getExtensionAttributes') ? $source->getExtensionAttributes() : null;
            if ($extAttributes && method_exists($extAttributes, 'getCpf')) {
                $cpf  = $cpf ?: $this->onlyDigits($extAttributes->getCpf());
                $cnpj = $cnpj ?: $this->onlyDigits($extAttributes->getCnpj());
            }

            if ($cpf || $cnpj) {
                return [$cpf, $cnpj];
            }
        }

        // Último recurso: taxvat do cliente / vat_id do endereço
        foreach ([$order->getCustomerTaxvat(), $shippingAddress->getVatId(), $order->getBillingAddress() ? $order->getBillingAddress()->getVatId() : null] as $doc) {
            $doc = $this->onlyDigits($doc);
            if (strlen($doc) == 11) {
                return [$doc, null];
            }
            if (strlen($doc) == 14) {
                return [null, $doc];
            }
        }

        return [null, null];
    }

    private function onlyDigits($value): string
    {
        return preg_replace('/\D/', '', (string)$value);
    }

    private function getItemDetails($item)
    {
        $weightAttr = $this->getConfigData('weight');
        $widthAttr  = $this->getConfigData('width');
        $heightAttr = $this->getConfigData('height');
        $lengthAttr = $this->getConfigData('length');

        $defaultHeight = $this->getConfigData('default_height');
        $defaultWidth  = $this->getConfigData('default_width');
        $defaultLength = $this->getConfigData('default_length');

        $price  = 0;
        $weight = 0;

        $width  = 0;
        $height = 0;
        $length = 0;

        $sku = null;
        $qty = intval($item->getData('qty_ordered'));

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        if ($item->getHasChildren()) {
            foreach ($item->getChildren() as $child) {
                if (!$child->getProduct()->isVirtual()) {
                    $product = $objectManager->create('Magento\Catalog\Model\Product')->load($child->getProductId());

                    $sku = $product->getData('sku');

                    $price += ($item->getPrice() - $item->getDiscountAmount());
                    $parentIds = $objectManager->create('Magento\GroupedProduct\Model\Product\Type\Grouped')->getParentIdsByChild($product->getId());
                    if (!$parentIds) {
                        $parentIds = $objectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable')->getParentIdsByChild($product->getId());

                        if ($parentIds) {
                            $parentProd = $objectManager->create('Magento\Catalog\Model\Product')->load($parentIds[0]);

                            $weight += (float)$parentProd->getData($weightAttr);
                            $width  += ($parentProd->getData($widthAttr) > 0 ? $parentProd->getData($widthAttr) : $defaultWidth);
                            $height += ($parentProd->getData($heightAttr) > 0 ? $parentProd->getData($heightAttr) : $defaultHeight);
                            $length += ($parentProd->getData($lengthAttr) > 0 ? $parentProd->getData($lengthAttr) : $defaultLength);
                        }
                    }
                }
            }
        } else {
            $product = $objectManager->create(Product::class)->load($item->getProductId());

            if ($product->getTypeId() == 'simple') {
                $sku = $product->getData('sku');

                $price += (float)(!is_null($product->getData('special_price')) ? $product->getData('special_price') : $product->getData('price'));

                $weight += (float)$product->getData($weightAttr);
                $width  += ($product->getData($widthAttr) > 0 ? $product->getData($widthAttr) : $defaultWidth);
                $height += ($product->getData($heightAttr) > 0 ? $product->getData($heightAttr) : $defaultHeight);
                $length += ($product->getData($lengthAttr) > 0 ? $product->getData($lengthAttr) : $defaultLength);
            }
        }

        $weightUnit = $this->getConfigData('tp_vl_peso');
        if ($weightUnit == 'gr') {
            $weight = $weight / 1000;
        }

        return compact('qty', 'sku', 'price', 'weight', 'width', 'height', 'length');
    }

    /**
     * Retrieve information from carrier configuration
     *
     * @param   string $field
     * @return  false|string
     */
    private function getConfigData($field)
    {
        if (empty($this->_code)) {
            return false;
        }

        $path = 'carriers/' . $this->_code . '/' . $field;

        return $this->_scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeId ?? $this->storeManager->getStore()->getId()
        );
    }

    private function getShipmentConfigData($order, $field)
    {
        return $this->_scopeConfig->getValue(
            $field,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );
    }
}
