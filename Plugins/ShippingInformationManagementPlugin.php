<?php

declare(strict_types=1);

namespace Megleo\Delivery\Plugins;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;

/**
 * Persiste CPF/CNPJ (extension attributes) nas colunas cpf/cnpj do quote_address
 * ao salvar as informações de entrega do checkout.
 */
class ShippingInformationManagementPlugin
{
    public function beforeSaveAddressInformation(
        ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        foreach ([$addressInformation->getShippingAddress(), $addressInformation->getBillingAddress()] as $address) {
            if (!$address || !$address->getExtensionAttributes()) {
                continue;
            }

            $extAttributes = $address->getExtensionAttributes();

            if (!empty($extAttributes->getCpf())) {
                $address->setCpf($extAttributes->getCpf());
            }
            if (!empty($extAttributes->getCnpj())) {
                $address->setCnpj($extAttributes->getCnpj());
            }
        }

        return [$cartId, $addressInformation];
    }
}
