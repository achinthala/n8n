/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'escaper',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/totals'
], function (Component, escaper, customerData, totals) {
    'use strict';
    var quoteItemData = window.checkoutConfig.quoteItemData;
    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/summary/item/details',
            allowedTags: ['b', 'strong', 'i', 'em', 'u']
        },
        quoteItemData: quoteItemData,
        initialize: function () {
          this._super();

          // This will update items in order summary
          totals.isLoading.subscribe(() => {
            customerData.reload(['cart']).done((result) => {
              if (result.cart && result.cart.items) {
                result.cart.items.forEach((e) => {
                  window.checkoutConfig.imageData[e.item_id] = e.product_image;
                });
                this.quoteItemData = result.cart.items;
              }
            });
          });
        },
        /**
         * @param {Object} quoteItem
         * @return {String}
         */
        getNameUnsanitizedHtml: function (quoteItem) {

            var txt = document.createElement('textarea');

            txt.innerHTML = quoteItem.name;

            return escaper.escapeHtml(txt.value, this.allowedTags);
        },

        /**
         * @param {Object} quoteItem
         * @return {String}Magento_Checkout/js/region-updater
         */
        getValue: function (quoteItem) {
            return quoteItem.name;
        },

        getParsedOptions(quoteItem) {
            var poptions = JSON.parse(quoteItem.options);
            var optionValues = [];

            if (Object.keys(poptions).length) {
                for (var x in poptions) {
                    if (poptions.hasOwnProperty(x)) {
                        optionValues.push(poptions[x]);
                    }
                }
            }
            return optionValues;
        },
        getCutPrice: function (quoteItem) {
            var item = this.getItem(quoteItem.item_id);
            return item.cutPrice || ''; // Return cutPrice value
        },
        getStockStatus: function (quoteItem) {
            var item = this.getItem(quoteItem.item_id);
            return item.stock_status || ''; // Return stock_status value
        },
        getDiscountPercentage: function (quoteItem) {
            var item = this.getItem(quoteItem.item_id);
            return item.discount_percentage || ''; // Return discount_percentage value
        },
        getCalculatedSquareFeetPrice: function (quoteItem) {
            var item = this.getItem(quoteItem.item_id);
            return item.calculated_square_feet_price || ''; // Return calculated_square_feet_priceI value
        },
        getShippingEstimate: function (quoteItem) {
            var item = this.getItem(quoteItem.item_id);
            return item.shipping_estimate || ''; // Return calculated_square_feet_priceI value
        },
        getProductWeight: function (quoteItem) {
            var item = this.getItem(quoteItem.item_id);
            return item.product_weight || ''; // Return product_weight value
        },
        getItem: function (item_id) {
            var itemElement = null;
            _.each(this.quoteItemData, function (element, index) {
                if (element.item_id == item_id) {
                    itemElement = element;
                }
            });
            return itemElement;
        }
    });
});
