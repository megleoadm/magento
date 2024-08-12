define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote'
], function ($, wrapper, quote) {
    'use strict';

    return function (setBillingAddressAction) {

        return wrapper.wrap(setBillingAddressAction, function (originalAction, messageContainer) {

            var billingAddress = quote.billingAddress();
            if (billingAddress != undefined) {

                if (billingAddress['extension_attributes'] === undefined) {
                    billingAddress['extension_attributes'] = {};
                }

                if (billingAddress.customAttributes != undefined) {
                    var attribute = billingAddress.customAttributes.find(
                        function (element) {
                            return element.attribute_code === 'cpf';
                        }
                    );
                    billingAddress['extension_attributes']['cpf'] = attribute?.value ?? '';

                    var attribute = billingAddress.customAttributes.find(
                        function (element) {
                            return element.attribute_code === 'cnpj';
                        }
                    );
                    billingAddress['extension_attributes']['cnpj'] = attribute?.value ?? '';
                }

            }

            return originalAction(messageContainer);
        });
    };
});