/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Ui/js/model/messageList'
], function ($, wrapper, quote, messageList) {
    'use strict';
	
	// Register subscription
    quote.shippingAddress.subscribe(function (address) {
		console.log('shipping address change');
        if (!address || !address.street) {
            return;
        }

        var street = Array.isArray(address.street)
            ? address.street.join(' ')
            : address.street;

        if (!/\d/.test(street)) {
            messageList.addErrorMessage({
                message: 'Shipping address must contain a number.'
            });
        }
    });

    return function (setShippingInformationAction) {

        return wrapper.wrap(setShippingInformationAction, function (originalAction) {
            var shippingAddress = quote.shippingAddress();

            // Validate street address
            var street = '';
            if (shippingAddress && shippingAddress.street) {
                street = shippingAddress.street.join(' ');
            }

            if (!/\d/.test(street)) {
                messageList.addErrorMessage({
                    message: 'Invalid Address. Please contact us if this error persists.'
                });

                window.location.hash = '#shipping';
				$('html, body').animate({
					scrollTop: 0
				}, 300);
                return $.Deferred().reject().promise();
            }

            if (shippingAddress.extension_attributes === undefined) {
                shippingAddress.extension_attributes = {};
            }

            var attribute;
            if (shippingAddress.customAttributes) {
				if (Array.isArray(shippingAddress.customAttributes)) {
					attribute = shippingAddress.customAttributes.find(function (element) {
						return element.attribute_code === 'phone_ext';
					});
				} else {
					attribute = shippingAddress.customAttributes['phone_ext'];
				}
            }

            if (attribute && attribute.value) {
				shippingAddress.extension_attributes.phone_ext = attribute.value;
			}

            return originalAction();
        });
    };
});