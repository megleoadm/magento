/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote'
], function ($, wrapper, quote) {
    'use strict';

    return function (setShippingInformationAction) {

        return wrapper.wrap(setShippingInformationAction, function (originalAction, messageContainer) {

            var shippingAddress = quote.shippingAddress();
            if (shippingAddress != undefined) {

                if (shippingAddress['extension_attributes'] === undefined) {
                    shippingAddress['extension_attributes'] = {};
                }

                if (shippingAddress.customAttributes != undefined) {
                    var attribute = shippingAddress.customAttributes.find(
                        function (element) {
                            return element.attribute_code === 'cpf';
                        }
                    );
                    shippingAddress['extension_attributes']['cpf'] = attribute?.value ?? '';

                    var attribute = shippingAddress.customAttributes.find(
                        function (element) {
                            return element.attribute_code === 'cnpj';
                        }
                    );
                    shippingAddress['extension_attributes']['cnpj'] = attribute?.value ?? '';
                }

            }

            return originalAction(messageContainer);
        });
    };
});