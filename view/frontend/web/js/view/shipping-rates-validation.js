define(
    [
        "uiComponent",
        "Magento_Checkout/js/model/shipping-rates-validator",
        "Magento_Checkout/js/model/shipping-rates-validation-rules",
        "Megleo_Delivery/js/model/shipping-rates-validator",
        "Megleo_Delivery/js/model/shipping-rates-validation-rules"
    ],
    function (a, b, c, d, e) {
        b.registerValidator("megleo_delivery", d);
        c.registerRules("megleo_delivery", e);
        return a
    }
);