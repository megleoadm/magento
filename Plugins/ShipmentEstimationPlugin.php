<?php

namespace Megleo\Delivery\Plugins;

use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\ShipmentEstimationInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface as Logger;

class ShipmentEstimationPlugin
{
    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var AddressInterface
     */
    private $_addressInformation;

    public function __construct(
        Logger $logger,
        StoreManagerInterface $storeManager,
        AddressInterface $addressInformation
    ) {
        $this->_storeManager = $storeManager;
        $this->_addressInformation = $addressInformation;
    }

    public function aroundEstimateByExtendedAddress(
        ShipmentEstimationInterface $subject,
        \Closure $proceed,
        $cartId,
        AddressInterface $address
    ) {
        $addressAttributes = $address->getExtensionAttributes();
        $this->_addressInformation->setExtensionAttributes($addressAttributes);

        $shippingMethods = $proceed($cartId, $address);
        return $shippingMethods;
    }
}
