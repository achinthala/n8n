/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Configurable variation left qty.
 */
define([
    'jquery',
    'underscore',
    'mage/url'
], function ($, _, urlBuilder) {

function loadMore(last_id){
 $.ajax({
                url: urlBuilder.build('homeproductlist/products/productlist'),
                dataType: 'json',
                data: {
                    'last_id': last_id
                }
            }).done(function (response) {
                //console.log(response);
				$('#homeinfiniteproduct').append(response.html);
				$('#homeinfiniteproduct').attr("fnhit", "1");
            })
}

    
});
