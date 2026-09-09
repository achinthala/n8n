/**
 * jQuery Validate rule for customer account (and other Luma) forms — exactly 10 digits.
 */
define([
    'jquery',
    'jquery/validate',
    'mage/translate'
], function ($, $v, $t) {
    'use strict';

    $.validator.addMethod(
        'validate-dcw-phone-min-digits',
        function (value) {
            if (value === '' || value == null) {
                return true;
            }

            var s = String(value).trim();
            if (!/^[\d\s+().\-]+$/.test(s)) {
                return false;
            }

            return s.replace(/\D/g, '').length === 10;
        },
        function (params, element) {
            var v = String($(element).val() || '').trim();
            if (v && !/^[\d\s+().\-]+$/.test(v)) {
                return $t(
                    'Please enter a valid 10-digit phone number.'
                );
            }
            return $t('Please enter a valid 10-digit phone number.');
        }
    );
});
