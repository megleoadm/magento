define(
    [
        "jquery",
        "mageUtils",
        "Megleo_Delivery/js/model/shipping-rates-validation-rules",
        "mage/translate"
    ],
    function ($, utils, validationRules, $t) {
        'use strict';
        return {
            validationErrors: [],
            validate: function (f) {
                var g = this;
                this.validationErrors = [];
                $.each(validationRules.getRules(), function (a, h) {
                    h.required && utils.isEmpty(f[a]) && (a = $t("Field ") + a + $t(" is required."), g.validationErrors.push(a))
                });
                return !this.validationErrors.length
            }
        }
    }
);