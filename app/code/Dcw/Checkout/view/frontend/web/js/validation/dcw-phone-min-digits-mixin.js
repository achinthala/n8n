/**
 * Adds dcw-phone-min-digits validation (exactly 10 digit characters; non-digits ignored).
 */
define([
    'mage/translate'
], function ($t) {
    'use strict';

    return function (rules) {
        rules['dcw-phone-min-digits'] = {
            handler: function (value) {
                if (value === null || value === undefined || String(value).trim() === '') {
                    return true;
                }

                var s = String(value).trim();
                if (!/^[\d\s+().\-]+$/.test(s)) {
                    return false;
                }

                return s.replace(/\D/g, '').length === 10;
            },
            message: $t(
                'Please enter a valid 10-digit phone number.'
            )
        };

        return rules;
    };
});
