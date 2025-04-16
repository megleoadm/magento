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
        $shippingMethod = $order->getShippingMethod(true);

        if ($shippingMethod->getData('carrier_code') == 'megleo') {
            $orderId = $order->getId();
            $quote = $this->quoteFactory->create()->load($order->getQuoteId());

            // $transportadoraId = $this->getTransportadoraId($quote);
            $shippingAddress  = $order->getShippingAddress();

            $token  = $this->getConfigData('codigo_acesso');
            $client = new Client($token);

            $criarPedido = $client->criarPedidoSimplificado();

            foreach ($order->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual()) {
                    continue;
                }

                $criarPedido->withProduct($this->getItemDetails($item));
            }

            $customer = $this->convertCustomer($quote, $shippingAddress);

            $criarPedido
                ->withOrderId($orderId)
                ->withCepOrigem($this->getFromZip($order))
                ->withCepDestino($shippingAddress->getData('postcode'))
                ->withValorNota($order->getSubtotal())
                ->withCustomer($customer);

            try {
                $response = $criarPedido->criar();

                if (!empty($response->error)) {
                    $this->logger->critical("ERRO API Megleo retornou um erro /api/v1_2/pedidos/criar_simplificado: " . $response->error);
                    return false;
                }
            } catch (Exception $e) {
                $this->logger->critical("ERRO chamada à API Megleo /api/v1_2/pedidos/criar_simplificado: " . $e->getMessage());
                return false;
            }

            $dataResponse = $response->data ?? [];
            if (!is_array($dataResponse)) {
                $dataResponse = [$dataResponse];
            }

            $metadata = [];
            foreach ($dataResponse as $result) {
                $data = new stdClass();

                $data->cnpj_pagador = $result->cnpj_pagador;
                $data->endereco_coleta = $result->endereco_coleta;
                $data->transportadora = $result->transportadora;
                $data->volumes = $result->volumes;
                $data->valor_fatura = $result->valor_fatura;
                
                $metadata[] = $data;
            }

            $megleoMetadata = json_encode($metadata, JSON_PRETTY_PRINT);

            $order->setMegleoMetadata($megleoMetadata);
            $order->save();
        }
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

    private function convertCustomer($quote, $shippingAddress): stdClass
    {
        $recipient  = new stdClass();

        $cpf = $shippingAddress->getData('cpf');
        $cnpj = $shippingAddress->getData('cnpj');

        if (empty($cpf) && empty($cnpj)) {
            $customerId = $quote->getData('customer_id');

            if (!$customerId) {
                $this->logger->critical("ERRO ao obter o perfil do cliente: " . self::class . '::convertCustomer()');
                throw new Exception('ERRO ao obter o perfil do cliente');
            }

            $customer = $this->customerFactory->create()->load($customerId);

            $cpf  = $customer->getData('cpf');
            $cnpj = $customer->getData('cnpj');
        }

        $recipient->tipo_pessoa = !empty($cnpj) ? 'pessoa_juridica' : 'pessoa_fisica';
        $recipient->cpf  = $cpf;
        $recipient->cnpj = $cnpj;

        $recipient->telefone = $shippingAddress->getTelephone();
        $recipient->nome  = $quote->getData('customer_firstname') . ' ' . $quote->getData('customer_lastname');
        $recipient->email = $quote->getData('customer_email');

        return $recipient;
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
            $this->storeManager->getStore(),
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
