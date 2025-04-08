<?php

/**
 * 
 * Módulo de integração com a Megleo
 * 
 * @category     megleo
 * @package      Módulo de integração com a Megleo
 * @copyright    Copyright (c) 2024 megleo (https://www.megleo.com.br)
 *
 */

declare(strict_types=1);

namespace Megleo\Delivery\Model;

use Megleo\Delivery\Sdk\Client;
use Megleo\Delivery\Sdk\Errors;
use Megleo\Delivery\Sdk\Exceptions\MegleoException;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Psr\Log\LoggerInterface;

use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

use stdClass;

class Carrier extends AbstractCarrierOnline implements CarrierInterface
{
	const CODE = 'megleo_delivery';
	const COUNTRY = 'BR';

	protected $_code = self::CODE;
	protected $_freeMethod = null;

	private $fromZip = null;
	private $toZip = null;
	private $destCpf = null;
	private $destCnpj = null;
	private $hasFreeMethod = false;

	/**
	 * Rate result data
	 *
	 * @var Result|null
	 */
	protected $_result = null;

	private $packageValue;

	protected $freeMethodSameCEP = null;

	protected $megleoServiceList = [];

	protected $cartItems = [];

	private $logger;

	/**
	 * @var AddressInterface
	 */
	private $_addressInformation;

	/**
	 * @var CustomerSession
	 */
	private $_customerSession;

	protected $sourceRepository;
	protected $searchCriteriaBuilder;

	public function __construct(
		ScopeConfigInterface $scopeConfig,
		ErrorFactory $rateErrorFactory,
		LoggerInterface $logger,
		\Magento\Framework\Xml\Security $xmlSecurity,
		\Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
		\Magento\Shipping\Model\Rate\ResultFactory $rateFactory,
		\Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
		\Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
		\Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
		\Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
		\Magento\Directory\Model\RegionFactory $regionFactory,
		\Magento\Directory\Model\CountryFactory $countryFactory,
		\Magento\Directory\Model\CurrencyFactory $currencyFactory,
		\Magento\Directory\Helper\Data $directoryData,
		\Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
		AddressInterface $addressInformation,
		CustomerSession $customerSession,
		SourceRepositoryInterface $sourceRepository,
		SearchCriteriaBuilder $searchCriteriaBuilder,
		array $data = []
	) {
		parent::__construct(
			$scopeConfig,
			$rateErrorFactory,
			$logger,
			$xmlSecurity,
			$xmlElFactory,
			$rateFactory,
			$rateMethodFactory,
			$trackFactory,
			$trackErrorFactory,
			$trackStatusFactory,
			$regionFactory,
			$countryFactory,
			$currencyFactory,
			$directoryData,
			$stockRegistry,
			$data
		);

		$this->logger = $logger;
		$this->_addressInformation = $addressInformation;
		$this->_customerSession = $customerSession;

		$this->sourceRepository = $sourceRepository;
		$this->searchCriteriaBuilder = $searchCriteriaBuilder;
	}

	/**
	 * Processing additional validation to check if carrier applicable.
	 *
	 * @param \Magento\Framework\DataObject $request
	 * @return $this|bool|\Magento\Framework\DataObject
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 * @since 100.2.6
	 */
	public function processAdditionalValidation(\Magento\Framework\DataObject $request)
	{
		return $this;
	}

	public function getAllStoreSources()
	{
		$searchCriteria = $this->searchCriteriaBuilder->create();
		$sources = $this->sourceRepository->getList($searchCriteria);
		return $sources->getItems();
	}

	public function getFirstSourceZipCode()
	{
		$sources = $this->getAllStoreSources();
		foreach ($sources as $source) {
			if ($source->isEnabled() && !empty($source->getPostcode())) {
				return preg_replace('/\D/', '', $source->getPostcode());
			}
		}
		return false;
	}

	private function check(RateRequest $request)
	{
		if (!$this->getConfigFlag('active')) {
			return false;
		}

		$origCountry = $this->_scopeConfig->getValue(Shipment::XML_PATH_STORE_COUNTRY_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $request->getStoreId());
		$destCountry = $request->getDestCountryId();
		if ($origCountry != self::COUNTRY || $destCountry != self::COUNTRY) {
			$rate = $this->_rateErrorFactory->create();
			$rate->setCarrier($this->_code);
			$rate->setErrorMessage(Errors::getMessage('002'));
			$this->getRateResult()->append($rate);

			return false;
		}

		$this->fromZip = $this->getFirstSourceZipCode();
		$this->fromZip = preg_replace('/\D/', '', $this->fromZip ?? '');
		if (!preg_match('/^([0-9]{8})$/', $this->fromZip)) {
			$rate = $this->_rateErrorFactory->create();
			$rate->setCarrier($this->_code);
			$rate->setErrorMessage(Errors::getMessage('003'));
			$this->getRateResult()->append($rate);

			return false;
		}

		$documentsAttributes = $this->getShippingExtAttributes($request);

		if (empty($documentsAttributes['cpf']) && empty($documentsAttributes['cnpj'])) {
			$customer = $this->_customerSession->getCustomer();

			$cpf  = $customer->getData('cpf') ?? '';
			$cnpj = $customer->getData('cnpj') ?? '';
			$documentsAttributes = compact('cpf', 'cnpj');

			if (empty($documentsAttributes['cpf']) && empty($documentsAttributes['cnpj'])) {
				$documentsAttributes['cpf'] = null;
				$documentsAttributes['cnpj'] = null;

				// 	$rate = $this->_rateErrorFactory->create();
				// 	$rate->setCarrier($this->_code);
				// 	$rate->setErrorMessage(Errors::getMessage('402'));
				// 	$this->getRateResult()->append($rate);

				// 	return false;
			}
		}

		if ($documentsAttributes['cpf'] !== null) {
			$this->destCpf = preg_replace('/\D/', '', $documentsAttributes['cpf']);
			if (!preg_match('/^([0-9]{11})$/', $this->destCpf)) {
				if (!empty($this->destCpf)) {
					$rate = $this->_rateErrorFactory->create();
					$rate->setCarrier($this->_code);
					$rate->setErrorMessage(Errors::getMessage('005'));
					$this->getRateResult()->append($rate);
				}

				return false;
			}
		}

		if ($documentsAttributes['cnpj'] !== null) {
			$this->destCnpj = preg_replace('/\D/', '', $documentsAttributes['cnpj']);
			if (!empty($this->destCnpj) && !preg_match('/^([0-9]{14})$/', $this->destCnpj)) {
				if (!empty($this->destCnpj)) {
					$rate = $this->_rateErrorFactory->create();
					$rate->setCarrier($this->_code);
					$rate->setErrorMessage(Errors::getMessage('006'));
					$this->getRateResult()->append($rate);
				}

				return false;
			}
		}

		$price = 0;

		$this->cartItems = [];
		if ($request->getAllItems()) {
			foreach ($request->getAllItems() as $item) {
				if ($item->getProduct()->isVirtual()) {
					continue;
				}

				$details = $this->getItemDetails($item);

				$price += $details['price'] * $details['qty'];

				$this->cartItems[$details['sku']] = $details;
			}
		}

		$this->hasFreeMethod = $request->getFreeShipping();
		$this->_freeMethod   = $this->getConfigData('servico_gratuito');

		$this->packageValue  = $request->getBaseCurrency()->convert($price, $request->getPackageCurrency());
	}

	private function getShippingExtAttributes(RateRequest $request): array
	{
		$extAttributes = $this->_addressInformation->getExtensionAttributes();
		if (empty($extAttributes->getCpf()) && empty($extAttributes->getCnpj())) {
			foreach ($request->getAllItems() as $item) {
				$extAttributes = $item->getAddress()->getExtensionAttributes();

				if (empty($extAttributes->getCpf()) && empty($extAttributes->getCnpj())) {
					$quote = $item->getQuote();
					$shippingAddress = $quote->getShippingAddress();
					$cpf  = $shippingAddress->getData('cpf') ?? '';
					$cnpj = $shippingAddress->getData('cnpj') ?? '';
					return compact('cpf', 'cnpj');
				}

				break;
			}
		}

		$cpf  = $extAttributes->getCpf() ?? '';
		$cnpj = $extAttributes->getCnpj() ?? '';
		return compact('cpf', 'cnpj');
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
		$qty = intval($item->getQty());
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

	public function collectRates(RateRequest $request)
	{
		$this->toZip = $request->getDestPostcode();
		if (null == $this->toZip) {
			return $this->getRateResult();
		}

		$this->toZip = preg_replace('/\D/', '', $this->toZip);
		if (!preg_match('/^([0-9]{8})$/', $this->toZip)) {
			return $this->getRateResult();
		}

		if ($this->check($request) === false) {
			return $this->getRateResult();
		}

		$this->getQuotes();

		return $this->getRateResult();
	}

	public function getAllowedMethods()
	{
		return [];
	}

	protected function getQuotes()
	{
		$this->calcPrecoPrazo();

		if (sizeof($this->megleoServiceList) == 0) {
			$error = $this->_trackErrorFactory->create();
			$error->setCarrier($this->_code);
			$error->setErrorMessage(Errors::getMessage('001'));
			$this->getRateResult()->append($error);

			return $this->getRateResult();
		}

		foreach ($this->megleoServiceList as $servico) {
			$this->appendService($servico);
		}

		return $this->getRateResult();
	}

	private function appendService($servico)
	{
		$rate = null;
		$method = $servico->transportadora_nome;

		$rate = $this->_rateMethodFactory->create();
		$rate->setCarrier($this->_code);
		$rate->setCarrierTitle($this->getConfigData('title'));
		$rate->setData('method_description', 'ID ' . $servico->transportadoraId);
		$rate->setMethod($method . '_' . $servico->transportadora_cnpj);

		$title = $method;
		if ($this->getConfigData('prazo_entrega')) {
			$s = $this->getConfigData('mensagem_prazo_entrega');
			$title = sprintf($s, $title, intval($servico->prazoEntrega + $this->getConfigData('prazo_extra')));
		}

		if (isset($servico->msgPrazo)) {
			$title = $title . ' [' . $servico->msgPrazo . ']';
		}

		$title = substr($title, 0, 250);
		$rate->setMethodTitle($title);

		$taxaExtra = $this->getConfigData('taxa_extra');
		if ($taxaExtra) {
			$v1 = floatval(str_replace(',', '.', (string) $this->getConfigData('taxa_extra_valor')));
			$v2 = floatval(str_replace(',', '.', (string) $servico->preco));

			if ($taxaExtra == '2') {
				$rate->setPrice($v1 + $v2);
			} else if ($taxaExtra == '1') {
				$rate->setPrice($v2 + (($v1 * $v2) / 100));
			}
		} else {
			$rate->setPrice(floatval(str_replace(',', '.', (string) $servico->preco)));
		}

		if ($this->hasFreeMethod) {
			if ($method == $this->_freeMethod) {
				$v1 = floatval(str_replace(',', '.', (string)$this->getConfigData('servico_gratuito_desconto')));
				$p = $rate->getPrice();
				if ($v1 > 0 && $v1 > $p) {
					$rate->setPrice(0);
				}
			}

			if ($method == $this->freeMethodSameCEP) {
				$rate->setPrice(0);
			}
		}

		$rate->setCost(0);

		$this->getRateResult()->append($rate);
	}

	private function calcPrecoPrazo()
	{
		$token  = $this->getConfigData('codigo_acesso');
		$client = new Client($token);

		/**
		 * Preço
		 */
		$simularValor = $client->simularValor();

		foreach ($this->cartItems as $product) {
			$simularValor->withProduct($product);
		}

		$simularValor
			->withCepOrigem($this->fromZip)
			->withCepDestino($this->toZip)
			->withCnpjDestino($this->destCnpj)
			->withValorNota($this->packageValue);

		try {
			$valores = $simularValor->valores();
		} catch (MegleoException $exception) {
			$this->logger->critical($exception->getMessage());

			$rate = $this->_rateErrorFactory->create();
			$rate->setCarrier($this->_code);
			$rate->setErrorMessage(Errors::getMessage('401'));
			$this->getRateResult()->append($rate);

			return $this->getRateResult();
		}

		if (empty($valores->error)) {
			/**
			 * Merge
			 */
			foreach ($valores as $result) {
				$service = new stdClass();

				$service->transportadoraId = $result->id;
				$service->transportadora_nome = $result->transportadora_nome;
				$service->transportadora_cnpj = $result->transportadora_cnpj;
				$service->preco = $result->valor_fatura_com_coleta;
				$service->prazoEntrega = $result->dias_normal;
				if (isset($result->msgPrazo)) {
					$service->msgPrazo = $result->msgPrazo;
				}


				$this->megleoServiceList[] = $service;
			}
		}
	}

	public function isTrackingAvailable()
	{
		return false;
	}

	/**
	 * Do shipment request to carrier web service, obtain Print Shipping Labels and process errors in response
	 *
	 * @param \Magento\Framework\DataObject $request
	 * @return \Magento\Framework\DataObject
	 */
	protected function _doShipmentRequest(\Magento\Framework\DataObject $request)
	{
		$result = new \Magento\Framework\DataObject();

		return $result;
	}

	/**
	 * Get result of request
	 *
	 * @return Result|null
	 */
	public function getTrackingResult()
	{
		if (!$this->_result) {
			$this->_result = $this->_trackFactory->create();
		}

		return $this->_result;
	}

	/**
	 * Get result of request
	 *
	 * @return Result|null
	 */
	public function getRateResult()
	{
		if (!$this->_result) {
			$this->_result = $this->_rateFactory->create();
		}

		return $this->_result;
	}
}
