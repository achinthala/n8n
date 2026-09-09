define(function () {
    'use strict';

    return function (target) {

        return function (addressData) {

            var orig = target(addressData);
            orig.isEditable = function () {
                return true;
            };

            return orig;
        }
    }
});