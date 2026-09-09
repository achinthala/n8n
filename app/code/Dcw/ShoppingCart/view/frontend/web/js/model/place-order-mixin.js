define([
  'jquery',
  'mage/utils/wrapper',
  'Magento_Customer/js/customer-data',
  'Magento_Checkout/js/model/quote',
  'Magento_Ui/js/model/messageList'
], function ($, wrapper, customerData, quote, messageList) {
  'use strict';

  return function (placeOrderAction) {

      /** Override default place order action and add is_subscribed to request */
      return wrapper.wrap(placeOrderAction, function (originalAction, paymentData, messageContainer) {
		var shippingAddress = quote.shippingAddress();
		var street = '';

		if (shippingAddress && shippingAddress.street) {
			street = Array.isArray(shippingAddress.street)
				? shippingAddress.street.join(' ')
				: shippingAddress.street;
		}

		console.log('Place Order Street:', street);

		if (!/\d/.test(street)) {
			messageList.addErrorMessage({
				message: 'Shipping address must contain a number.'
			});

			$('html, body').animate({
				scrollTop: 0
			}, 300);

			var deferred = $.Deferred();

			deferred.reject({
				responseText: JSON.stringify({
					message: 'Shipping address must contain a number.'
				})
			});

			return deferred.promise();
		}
        if (paymentData['extension_attributes'] === undefined) {
          paymentData['extension_attributes'] = {};
        }

        if (customerData.get('newsletter')().is_subscribed) {
          paymentData['extension_attributes']['is_subscribed'] = customerData.get('newsletter')().is_subscribed;
        }

        return originalAction(paymentData, messageContainer);
      });
  };
});
