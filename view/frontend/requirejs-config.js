var config = {
    map: {
        "*": {
            "Magento_Checkout/js/model/shipping-rate-processor/new-address": "Megleo_Delivery/js/model/shipping-rate-processor/new-address"
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/action/set-billing-address': {
                'Megleo_Delivery/js/action/set-billing-address-mixin': true
            },
            'Magento_Checkout/js/action/set-shipping-information': {
                'Megleo_Delivery/js/action/set-shipping-information-mixin': true
            },
            'Magento_Checkout/js/action/place-order': {
                'Megleo_Delivery/js/action/set-billing-address-mixin': true
            },
            'Magento_Checkout/js/action/create-billing-address': {
                'Megleo_Delivery/js/action/set-billing-address-mixin': true
            }
        }
    }
};