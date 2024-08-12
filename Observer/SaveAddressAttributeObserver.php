<?php

declare(strict_types=1);

namespace Megleo\Delivery\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Exception;

/**
 * Class SaveAddressAttributeObserver
 */
class SaveAddressAttributeObserver implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            $quote = $observer->getEvent()->getQuote();

            if ($quote->getBillingAddress()) {
                $order->getBillingAddress()->setCpf(
                    $quote->getBillingAddress()->getExtensionAttributes()->getCpf()
                );
            }
            
            if (!$quote->isVirtual()) {
                $order->getShippingAddress()->setCpf($quote->getShippingAddress()->getCpf());
            }

            if ($quote->getBillingAddress()) {
                $order->getBillingAddress()->setCnpj(
                    $quote->getBillingAddress()->getExtensionAttributes()->getCnpj()
                );
            }

            if (!$quote->isVirtual()) {
                $order->getShippingAddress()->setCnpj($quote->getShippingAddress()->getCnpj());
            }

            $order->save();
        } catch (Exception $e) {
        }
    }
}
