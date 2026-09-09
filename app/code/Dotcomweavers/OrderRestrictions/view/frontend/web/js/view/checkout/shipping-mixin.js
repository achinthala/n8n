//@ts-check
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/select-shipping-address',
        'Magento_Checkout/js/checkout-data',
        'Magento_Customer/js/model/address-list',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/address-converter',
        'mage/utils/objects',
        'uiRegistry'
    ], function (
        $,
        ko,
        quote,
        selectShippingAddressAction,
        checkoutData,
        addressList,
        urlBuilder,
        storage,
        errorProcessor,
        messageList,
        addressConverter,
        mageUtils,
        registry
    ) {
        'use strict';

      function waitForElement(selector, callback) {
          const observer = new MutationObserver((mutations) => {
              const element = document.querySelector(selector);
              if (element) {
                  observer.disconnect();
                  callback(element);
              }
          });

          observer.observe(document.body, {
              childList: true,
              subtree: true
          });
      }

        return function (target) {
            return target.extend({
                addressSaveState: ko.observable(0),

                initialize: function () {
                  this._super();

                  registry.async('checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.firstname')(function (shippingAddress, changes) {
                    if (shippingAddress) {
                      var emailField = registry.get('checkout.steps.shipping-step.shippingAddress.customer-email');
                      if (emailField) {
                        setTimeout(() => {
                            var emailPattern = /^\b[A-Z0-9._%-+]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b$/i
                            var email = emailField.email();
                            if (emailField.isCustomerLoggedIn()) {
                                waitForElement('#co-shipping-form', function() {
                                  $("#co-shipping-form").show();
                                  $("#checkout-step-shipping_method").show();
                                  $('#shipping-address-label').closest('div').addClass('step-active');
                                });
                                return;
                            }
                            if (emailPattern.test(email)) {
                              waitForElement('#co-shipping-form', function() {
                                $("#co-shipping-form").show();
                                $("#checkout-step-shipping_method").show();
                                $('#shipping-address-label').closest('div').addClass('step-active');
                              });
                            } else {
                                $("#co-shipping-form").hide();
                                $("#checkout-step-shipping_method").hide();
                            }
                        }, 2000);
                      }
                    }
                  });

                  window.addEventListener('remove-new-address', () => {
                    this.isNewAddressAdded(false);
                  });
                },
                /**
                 * Show address form popup
                 */
                showFormPopUp: function () {
                    $("#shipping-new-address-form").find(':input').each(function() {
                      if (this.name !== 'country_id') {
                        $(this).val('').change();
                      }
                      ko.dataFor(this).set('params.invalid', false);
                      if (ko.dataFor(this).error) {
                        ko.dataFor(this).error('');
                      }
                    });

                    this.source.set('params.invalid', false);

                    this._super();
                },
                /**
                 * Save new shipping address
                 */
                saveNewAddress: function () {
                    var addressData;
                    var self = this;
                    this.source.set('params.invalid', false);
                    this.triggerShippingDataValidateEvent();

                    if (!this.source.get('params.invalid')) {
                        addressData = this.source.get('shippingAddress');
                        if (mageUtils.isObject(addressData.street)) {
                            addressData.street = addressConverter.objectToArray(addressData.street);
                        }
                        var customerAddressId = 0;
                        if (addressData.customer_address_id != undefined) {
                            customerAddressId = Number(addressData.customer_address_id);
                        }

                        if (customerAddressId > 0) {
                            var newAddressList = ko.observableArray();
                            var finalAddress;
                            addressList().some(function(currentAddress) {
                                if (currentAddress.customerAddressId == addressData.customer_address_id) {
                                    finalAddress = Object.assign({}, currentAddress, addressData);
                                    self.updateCustomerAddress(finalAddress);
                                    if (self.addressSaveState() != 2) {
                                        selectShippingAddressAction(finalAddress);
                                        newAddressList.push(finalAddress);
                                    }
                                } else {
                                    newAddressList.push(currentAddress);
                                }
                                return false;
                            });

                            if (self.addressSaveState() != 2) {
                                addressList.removeAll();
                                if (this.getRegion('address-list')()[0].elems().length > 0) {
                                  this.getRegion('address-list')()[0].elems().some(function (item) {
                                    item.destroy();
                                  });
                                }

                                newAddressList().some(function(currentAddress) {
                                    addressList.push(currentAddress);

                                    return false;
                                });
                            }

                            if (finalAddress) {
                              setTimeout(() => {
                                selectShippingAddressAction(finalAddress);
                                checkoutData.setSelectedShippingAddress(finalAddress.getKey());
                              }, 500);
                            }

                            this.getPopUp().closeModal();
                            return;
                        }

                        this._super();
                    }
                },
                updateCustomerAddress: function (shippingAddress) {
                    var params = {
                        cartId: quote.getQuoteId()
                    };
                    var payload = {
                        addressInformation: {
                            shipping_address: shippingAddress
                        }
                    };
                    var self = this;
                    return storage.post(
                        urlBuilder.createUrl('/carts/mine/save-shipping-address', params),
                        JSON.stringify(payload)
                    ).done(function(response) {
                        console.log(response);
                    }).fail(function(response) {
                        self.addressSaveState(2);
                        errorProcessor.process(response, messageList);
                    });
                }
            });
        }
    }
);