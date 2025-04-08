<?php

namespace Megleo\Delivery\Model;

use Magento\Sales\Model\Order as MagentoOrder;

class Order extends MagentoOrder
{
    public function getMegleoMetadata()
    {
        return $this->getData('megleo_metadata');
    }

    public function setMegleoMetadata($metadata)
    {
        return $this->setData('megleo_metadata', $metadata);
    }
}
