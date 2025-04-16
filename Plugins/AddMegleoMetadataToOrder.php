<?php

declare(strict_types=1);

namespace Megleo\Delivery\Plugins;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;

class AddMegleoMetadataToOrder
{
    private ExtensionAttributesFactory $extensionAttributesFactory;

    public function __construct(
        ExtensionAttributesFactory $extensionAttributesFactory
    ) {
        $this->extensionAttributesFactory = $extensionAttributesFactory;
    }

    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ) {
        $extensionAttributes = $order->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionAttributesFactory->create(OrderInterface::class);
        }

        $megleoMetadata = $order->getData('megleo_metadata');
        $extensionAttributes->setMegleoMetadata($megleoMetadata);
        $order->setExtensionAttributes($extensionAttributes);

        return $order;
    }

    public function afterGetList(
        OrderRepositoryInterface $subject,
        \Magento\Sales\Api\Data\OrderSearchResultInterface $searchResult
    ) {
        foreach ($searchResult->getItems() as $order) {
            $this->afterGet($subject, $order);
        }
        return $searchResult;
    }
}
