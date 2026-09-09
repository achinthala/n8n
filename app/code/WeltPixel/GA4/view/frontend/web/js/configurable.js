define([
    'jquery',
    'priceUtils',
    'mage/url'
], function ($, priceUtils, urlBuilder) {
    'use strict';

    return function (widget) {
        $.widget('mage.configurable', widget, {
            _reloadPrice: function () {
                this._super();
                this._UpdateChilProductSelect();
            },

            _UpdateChilProductSelect: function () {
                let $widget = this;
                urlBuilder.setBaseUrl(window.BASE_URL);
                let requestUrl = urlBuilder.build("weltpixel_ga4/track/childproduct/");

                if ($('body').hasClass('catalog-product-view') && (window.ga4ParentVsChild == 'child')) {
                    let productId = $widget.simpleProduct;
                    if (productId) {

                        let variants = [];

                        if (window.ga4VariantEnabled == '1') {

                            let jsonAttributes = $widget.options.spConfig.attributes;

                            for (let i in jsonAttributes) {
                                let variant = '';
                                variant += jsonAttributes[i].label;
                                var attributeId = $widget.options.spConfig.index[productId][jsonAttributes[i].id];
                                for (let j in jsonAttributes[i].options) {
                                    if (jsonAttributes[i].options[j].id == attributeId) {
                                        variant += ': ' + jsonAttributes[i].options[j].initialLabel
                                    }
                                }
                                variants.push(variant);
                            }
                        }
                        let parent_product_id = $('input[name="product"]').attr('value');

                        $.post({
                            url: requestUrl,
                            global: false,
                            data: {
                                'parent_product_id': parent_product_id,
                                'product_id': productId,
                                'variant': variants.join(' | ')
                            }
                        }, function (pushData) {
                            if (window.ga4AllowServices && pushData) {
                                window.dataLayer.push({ecommerce: null});
                                window.dataLayer.push(pushData);
                            }
                        });
                    }
                }
            }
        });

        return $.mage.configurable;
    }
});
