/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'Magento_SalesRule/js/view/summary/discount'
], function ($, _, Component, quote, discountView) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/summary/shipping'
        },
        quoteIsVirtual: quote.isVirtual(),
        totals: quote.getTotals(),
        _hasLoggedInitialShippingPrice: false,
		
		initialize: function () {
        this._super();

        quote.shippingAddress.subscribe(function (address) {

            if (address && address.telephone) {

                setTimeout(function () {

                    const klaviyoField = document.querySelector(
                        '.kl_sms_phone_number-field input'
                    );

                    if (klaviyoField) {

                        klaviyoField.disabled = false;

                        klaviyoField.value = address.telephone;

                        klaviyoField.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));

                        klaviyoField.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));

                        klaviyoField.disabled = true;

                    }

                }, 500);
            }
        });

        return this;
    },

        /**
         * @return {*}
         */
        getShippingMethodTitle: function () {
            var shippingMethod,
                shippingMethodTitle = '';

            if (!this.isCalculated()) {
                return '';
            }
            shippingMethod = quote.shippingMethod();

            if (!_.isArray(shippingMethod) && !_.isObject(shippingMethod)) {
                return '';
            }

            if (typeof shippingMethod['method_title'] !== 'undefined') {
                shippingMethodTitle = ' - ' + shippingMethod['method_title'];
            }

            return shippingMethodTitle ?
                shippingMethod['carrier_title'] + shippingMethodTitle :
                shippingMethod['carrier_title'];
        },

        /**
         * @return {*|Boolean}
         */
        isCalculated: function () {
            return this.totals() && this.isFullMode() && quote.shippingMethod() != null; //eslint-disable-line eqeqeq
        },

        /**
         * @return {*}
         */
        getValue: function () {
            var price;

            if (!this.isCalculated()) {
                return this.notCalculatedMessage;
            }

            price =  this.totals()['shipping_amount'];
            console.log('checkoutConfig.totalsData.shipping_amount', price);

            if (!this._hasLoggedInitialShippingPrice) {
                this._hasLoggedInitialShippingPrice = true;
                return 'Finding your best rate';
            }
            if (parseFloat(price) === 0) {
                return 'FREE SHIPPING';
            }
            
            // Force set the checkoutConfig values to ensure they're updated
            if (typeof window !== 'undefined' && window.checkoutConfig) {
                // Ensure totalsData exists
                if (!window.checkoutConfig.totalsData) {
                    window.checkoutConfig.totalsData = {};
                }
                
                // Force set the shipping amount
                window.checkoutConfig.totalsData.shipping_amount = price;
                window.checkoutConfig.totalsData.base_shipping_amount = price;
                
                console.log('Force set checkoutConfig.totalsData.shipping_amount to:', price);
            }
            
            return this.getFormattedPrice(price);
        },

        /**
         * @return {String}
         */
        getShippingCssClass: function () {
            var price;

            if (!this.isCalculated()) {
                return '';
            }

            price = this.totals()['shipping_amount'];

            return parseFloat(price) === 0 ? 'text-green' : 'text-black';
        },

        /**
         * If is set coupon code, but there wasn't displayed discount view.
         *
         * @return {Boolean}
         */
        haveToShowCoupon: function () {
            var couponCode = this.totals()['coupon_code'];

            if (typeof couponCode === 'undefined') {
                couponCode = false;
            }

            return couponCode && !discountView().isDisplayed();
        },

        /**
         * Returns coupon code description.
         *
         * @return {String}
         */
        getCouponDescription: function () {
            if (!this.haveToShowCoupon()) {
                return '';
            }

            return '(' + this.totals()['coupon_code'] + ')';
        }
    });
});
