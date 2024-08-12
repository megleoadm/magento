define([], function () {
    'use strict';
    return {
        getRules: function () {
            return {
                postcode: {
                    required: true
                },
                country_id: {
                    required: true
                },
                cpf: {
                    required: true
                },
                cnpj: {
                    required: false
                },
            }
        }
    }
});