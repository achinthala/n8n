/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */

define([
    'ko',
    'Magento_Checkout/js/model/quote'
], function (ko, quote) {
    'use strict';

    return function (coTaxModule) {
        coTaxModule.prototype.coloradoTaxTitle = function () {
            var shippingAddress = quote.shippingAddress(); // Get the shipping address object
            if (shippingAddress && shippingAddress.region) {
                var state = shippingAddress.region; // Get the state (region) name
                if (state == 'Colorado') {
                    return this.getTotalTaxTitle().concat(' (Including Colorado Retail Delivery Fee)');
                } else {
                    return this.getTotalTaxTitle();
                }
            } else {
                console.log("Shipping address or region is not defined.");
                return '';
            }
        };

        return coTaxModule;
    };
});
