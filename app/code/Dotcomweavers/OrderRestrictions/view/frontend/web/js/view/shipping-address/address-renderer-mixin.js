//@ts-check
define([
  'jquery',
  'Magento_Ui/js/modal/confirm',
  'Magento_Checkout/js/model/quote',
  'mage/storage',
  'Magento_Checkout/js/model/url-builder',
  'Magento_Checkout/js/model/error-processor',
  'Magento_Ui/js/model/messageList',
  'Magento_Checkout/js/checkout-data',
  'Magento_Customer/js/model/address-list'
],
function ($, confirmation, quote, storage, urlBuilder, errorProcessor, messageList, checkoutData, addressList) {
  "use strict";

  // @see Magento_Checkout/js/view/shipping-address/address-renderer/default
  var mixin = {
      /**
         * Edit address.
         */
      editAddress: function () {
        $("#co-shipping-form").show();
        $("#checkout-step-shipping_method").show();
        $('#shipping-address-label').closest('div').addClass('step-active');
        this.assignAddress();
        this._super();
      },
      deleteAddress: function() {
        var address = this.address();
        var msg = 'Are you sure to delete existing address?';
        var self = this;
        if (address.customer_address_id && address.customerAddressId) {
          msg = 'Are you sure to delete new address?';
        }

        confirmation({
          title: $.mage.__('Address Delete'),
          content: $.mage.__(msg),
          actions: {
              confirm: function() {
                var params = {
                  cartId: quote.getQuoteId()
                };
                var payload = {
                    addressInformation: {
                        shipping_address: address
                    }
                };
                if (!address.customer_address_id && !address.customerAddressId) {
                  addressList().forEach(function (e, i) {
                    if (e.customerAddressId === undefined) {
                      addressList().splice(i, 1);
                    }
                  });
                  self.destroy();
                  addressList(addressList());
                  checkoutData.setNewCustomerShippingAddress(null);
                  checkoutData.setShippingAddressFromData(null);
                  checkoutData.setSelectedShippingAddress(null);
                  window.dispatchEvent(new CustomEvent('remove-new-address'));
                  return true;
                }
                return storage.post(
                    urlBuilder.createUrl('/carts/mine/delete-shipping-address', params),
                    JSON.stringify(payload)
                ).done(function(response) {
                    self.destroy();
                    checkoutData.setNewCustomerShippingAddress(null);
                    checkoutData.setShippingAddressFromData(null);
                    checkoutData.setSelectedShippingAddress(null);
                    addressList().forEach(function (e, i) {
                      if (e.customerAddressId === address.customerAddressId) {
                        addressList().splice(i, 1);
                      }
                    });
                    addressList(addressList());
                }).fail(function(response) {
                    errorProcessor.process(response, messageList);
                });
              },
          }
      });
      },

      /**
       * Show popup.
       */
      showPopup: function () {
          $("#co-shipping-form").show();
          $("#checkout-step-shipping_method").show();
          $('#shipping-address-label').closest('div').addClass('step-active');
          this.assignAddress();
          this._super();
      },

      assignAddress: function () {
        var address = this.address();
        $("#shipping-new-address-form").find(':input').each(function() {
            switch(this.name) {
              case 'firstname':
                    $(this).val(address.firstname).change();
                    break;
                case 'lastname':
                    $(this).val(address.lastname).change();
                    break;
                case 'street[0]':
                    $(this).val(address.street[0]).change();
                    break;
                case 'street[1]':
                    $(this).val(address.street[1]).change();
                    break;
                case 'city':
                    $(this).val(address.city).change();
                    break;
                case 'postcode':
                    $(this).val(address.postcode).change();
                    break;
                case 'region':
                    $(this).val(address.region).change();
                    break;
                case 'region_id':
                    $(this).val(address.regionId).change();
                    break;
                case 'telephone':
                    $(this).val(address.telephone).change();
                    break;
                case 'company':
                    $(this).val(address.company).change();
                    break;
                case 'country_id':
                    if (Boolean(address.countryId)) {
                      address.countryId = 'US';
                    }
                    $(this).val(address.countryId).change();
                    break;
                case 'customer_address_id':
                    $(this).val(address.customerAddressId).change();
                    break;
                case 'custom_attributes[phone_ext]':
                    if ((address.customer_address_id || address.customerAddressId) && address.custom_attributes) {
                      address.customAttributes = address.custom_attributes;
                    }
                    if (address.customAttributes && !address.customAttributes.length) {
                      $(this).val('');
                      break;
                    }
                    if (address.customAttributes) {
                      $(this).val(address.customAttributes.filter((e) => { return e.attribute_code == 'phone_ext'; })[0].value).change();
                    }
                    if (address.customAttributes && address.customAttributes.phone_ext) {
                      $(this).val(address.customAttributes.phone_ext);
                    }
                    break;

            }
        });
    }
  };

  return function (target) { // target == Result that Magento_Ui/.../columns returns.
      return target.extend(mixin); // new result that all other modules receive
  };
});
