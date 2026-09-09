define(['jquery', 'jquery/validate', 'mage/translate'], function ($) {
    'use strict';

    return function () {
        $.validator.addMethod(
            'validate-us-holidays',
            function (value) {
                if ($.mage.isEmptyNoTrim(value)) {
                    return true;
                }

                var regex = /^(0[1-9]|1[0-2])\/(0[1-9]|[12]\d|3[01])\/\d{4}(,(0[1-9]|1[0-2])\/(0[1-9]|[12]\d|3[01])\/\d{4})*$/;

                return regex.test(value);
            },
            $.mage.__('Invalid date or format.')
        );
    }
});
