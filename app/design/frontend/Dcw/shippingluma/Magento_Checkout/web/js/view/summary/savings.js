// GOOD — uses totals only
define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/totals'
], function (Component, totals) {
    'use strict';

    return Component.extend({
        isDisplayed: function () {
            const totalSegments = totals.totals()?.total_segments || [];
            let discount = 0;
            totalSegments.forEach(segment => {
                if (segment.code === 'discount') {
                    discount = segment.value;
                }
            });

            const savings = window.checkoutConfig.savings || 0;
            const netSavings = (-savings) - (-discount);

            return netSavings !== 0;
        },

        getSavingsValue: function () {
            const savings = window.checkoutConfig.savings || 0;
            const discount = totals.totals()?.total_segments?.find(s => s.code === 'discount')?.value || 0;
            return this.getFormattedPrice(((-savings)));
        },

        getSavingsClass: function () {
            const savings = window.checkoutConfig.savings || 0;
            return savings > 0 ? 'has-savings' : 'no-savings';  
        }
    });
});
