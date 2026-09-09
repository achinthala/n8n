define([
    'jquery',
    'mage/url'
    ], function ($, urlBuilder) {
    "use strict";

    var wpGA4ServerSide = {
        pushClick: function(productId, listId, listName, index, elm) {
            urlBuilder.setBaseUrl(window.BASE_URL);
            if (elm && $(elm).length &&  $(elm).attr('onclick') != undefined) {
                var requestUrl = urlBuilder.build("weltpixel_ga4/track/productclick/");
                if (productId && listId && listName) {
                    $.post({
                        url: requestUrl,
                        global: false,
                        data: {
                            'product_id': productId,
                            'list_id': listId,
                            'list_name': listName,
                            'index': index
                        }
                    });
                }
            }
        },

        pushViewItemList: function(hashId) {
            if (hashId && $('#wp_ga4_server_side_view_item_list').length) {
                urlBuilder.setBaseUrl(window.BASE_URL);
                var requestUrl = urlBuilder.build("weltpixel_ga4/track/viewitemlist/");
                $.post({
                    url: requestUrl,
                    global: false,
                    data: {
                        'hash_id': hashId
                    }
                });
            }
        }
    };

    window.wpGA4ServerSide = wpGA4ServerSide;
    return wpGA4ServerSide;
});
