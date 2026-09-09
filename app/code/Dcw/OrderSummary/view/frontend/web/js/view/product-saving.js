define([
    'uiComponent',
    'ko',
    'Magento_Catalog/js/price-utils'
], function (Component, ko, priceUtils) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Dcw_OrderSummary/product-saving'
        },

        // Load savings value from window.checkoutConfig
        initialize: function () {
            this._super();
            
            // Calculate and format the savings value
            let rawSavingsValue = (-window.checkoutConfig.savings) - (-window.checkoutConfig.totalsData?.discount_amount || 0);
            
            // Make savingsValue an observable with formatted price
            this.savingsValue = ko.observable(this.formatPrice(rawSavingsValue));
        },

        // Format price using Magento's price-utils
        formatPrice: function (value) {
            return priceUtils.formatPrice(value, window.checkoutConfig.priceFormat);
        }
    });
});
