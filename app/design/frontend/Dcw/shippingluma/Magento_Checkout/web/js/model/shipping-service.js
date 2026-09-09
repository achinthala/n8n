/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
	'jquery',
    'ko',
    'Magento_Checkout/js/model/checkout-data-resolver',
	'Magento_Checkout/js/model/quote',
	'mage/url'
], function ($, ko, checkoutDataResolver, quote, url) {
    'use strict';

    var shippingRates = ko.observableArray([]);

    return {
        isLoading: ko.observable(false),

        /**
         * Set shipping rates
         *
         * @param {*} ratesData
         */
        setShippingRates: function (ratesData) {
            shippingRates(ratesData);
            shippingRates.valueHasMutated();
            checkoutDataResolver.resolveShippingRates(ratesData);

			if (quote.shippingAddress()){
				console.log(quote.shippingAddress());
				if(quote.shippingAddress().regionId!=""){
					var customerAddressId = quote.shippingAddress().customerAddressId;
					$('body').trigger('processStart');
					$.ajax({
						data:{
							customerAddressId: customerAddressId
						},
						type:'post',
						url:url.build('orderrestrictions/index/getreservedorderid'),
						dataType:	'json',
						success:function(response){

						$('body').trigger('processStop');
						},
						error:function (XMLHttpRequest, textStatus, errorThrown) {
							if (textStatus == 'timeout'){}
							else if (textStatus == 'error'){}
							else if (textStatus == 'parsererror'){}
						}
					});
					$('body').trigger('processStop');

				}
			}
        },

        /**
         * Get shipping rates
         *
         * @returns {*}
         */
        getShippingRates: function () {
            return shippingRates;
        }
    };
});
