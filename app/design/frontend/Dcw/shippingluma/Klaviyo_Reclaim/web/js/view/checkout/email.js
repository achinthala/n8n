!function(){if(!window.klaviyo){window._klOnsite=window._klOnsite||[];try{window.klaviyo=new Proxy({},{get:function(n,i){return"push"===i?function(){var n;(n=window._klOnsite).push.apply(n,arguments)}:function(){for(var n=arguments.length,o=new Array(n),w=0;w<n;w++)o[w]=arguments[w];var t="function"==typeof o[o.length-1]?o.pop():void 0,e=new Promise((function(n){window._klOnsite.push([i].concat(o,[function(i){t&&t(i),n(i)}]))}));return e}}})}catch(n){window.klaviyo=window.klaviyo||[],window.klaviyo.push=function(){var n;(n=window._klOnsite).push.apply(n,arguments)}}}}();

define([
  'uiComponent',
  'mage/url',
  'jquery',
  'Magento_Checkout/js/checkout-data',
  'uiRegistry',
  'domReady!'
], function (Component, url, $, checkoutData, registry) {
  'use strict';

  // initialize the customerData prior to returning the component
  var _klaviyoCustomerData = window.customerData;

  return Component.extend({
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
                    $("#co-shipping-form").show();
                    $("#checkout-step-shipping_method").show();
                    $('#shipping-address-label').closest('div').addClass('step-active');
                    return;
                }
                if (emailPattern.test(email)) {
                    $("#co-shipping-form").show();
                    $("#checkout-step-shipping_method").show();
                    $('#shipping-address-label').closest('div').addClass('step-active');
                } else {
                    $("#co-shipping-form").hide();
                    $("#checkout-step-shipping_method").hide();
                    $('#shipping-address-label').closest('div').removeClass('step-active');
                }
            }, 1000);
          }
        }
      });

      this._klaviyoCustomerData = _klaviyoCustomerData;
      var observer = new MutationObserver(function (mutationsList, observer) {
          var shippingForm = document.getElementById("co-shipping-form");
          var shippingFormBtn = document.getElementById("checkout-step-shipping_method");
            if (shippingForm) {
                $("#co-shipping-form").hide();
                $('#shipping-address-label').closest('div').removeClass('step-active');
            }
            if (shippingFormBtn) {
                console.log("checkout-step-shipping_method=====>")
                $("#checkout-step-shipping_method").hide();
            }
            if(shippingForm && shippingFormBtn) {
                observer.disconnect();
            }
        });
      observer.observe(document.body, { childList: true, subtree: true });
      this._email;
      this.handleCheckout();
      return this;
    },
      displayAddressCount: function () {
        console.log("displayAddressCount=====>")
          if (!this.isUserLoggedIn()) {
              return;
          }
          $.ajax({
              url: url.build('rest/V1/customers/me'),
              method: 'GET',
              contentType: 'application/json',
              dataType: 'json',
              success: function (customerData) {
                  cosnole.log("customerData=====>", customerData)
              },
              error: function () {
                  console.error('Failed to fetch customer address data.');
              }
          });
      },
    handleCheckout: function () {
      if (this.isUserLoggedIn() && this._email) {
        this.postUserEmail(this._email);
      } else {
        this.bindEmailListener();
      }
    },
    isUserLoggedIn: function () {
      this._email = this._klaviyoCustomerData ? this._klaviyoCustomerData.email : undefined;
      if (this._email) {
        return true;
      }
    },
    isKlaviyoActive: function() {
      return !!(window.klaviyo && window.klaviyo.identify);
    },
    bindEmailListener: function () {
      // jquery overrides this, so let's create an instance of the parent
      var self = this;
      console.log('Klaviyo_Reclaim - Binding to #customer-email');
        jQuery('#maincontent').delegate('#customer-email', 'keyup', function (event) {
            self._email = jQuery(this).val();

            // Check if the backspace key (keyCode 8) is pressed
            if (event.keyCode === 8 || event.which === 8) {
                console.log("Backspace detected, validating email...");
                self.validateEmail(self._email);
            }
        });
      jQuery('#maincontent').delegate('#customer-email', 'change', function (event) {
        if (!self.isKlaviyoActive()) {
          return;
        }

        self._email = jQuery(this).val();
        if (!window.klaviyo.isIdentified()) {
          window.klaviyo.push(['identify', {
            '$email': self._email
          }]);
        }
        self.postUserEmail(self._email);
      });
      setTimeout(() => {
        this.validateEmail(checkoutData.getCheckedEmailValue() || checkoutData.getInputFieldEmailValue());
      }, 2000);
    },
  validateEmail: function (email) {
      var emailPattern = /^\b[A-Z0-9._%-+]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b$/i
      if (emailPattern.test(email)) {
          $("#co-shipping-form").show();
          $("#checkout-step-shipping_method").show();
          $('#shipping-address-label').closest('div').addClass('step-active');
      } else {
          $("#co-shipping-form").hide();
          $("#checkout-step-shipping_method").hide();
          $('#shipping-address-label').closest('div').removeClass('step-active');
      }
  },
    postUserEmail: function (customer_email) {
     /*  $.ajax({
        url: url.build('reclaim/checkout/email'),
        method: 'POST',
        data: {
          'email': customer_email
        },
        success: function (data) {

          console.log('Klaviyo_Reclaim - Quote updated with customer email: ' + customer_email);
        }
      }); */
        var self = this;
        $.ajax({
            url: url.build('amasty_quote/cart/customer'),
            method: 'POST',
            data: {
              'email': customer_email,
              'submit_action': 'check_email'
            },
            success: function (data) {
                var fblink = document.querySelectorAll('li.facebook a');
                var googlelink = document.querySelectorAll('li.googleplus a');
                if (data.email) {
                    if (data.message === 'Email exists with facebook') {
                        this.isPasswordVisible = false;
                        fblink[0].click();
                    } else if(data.message === 'Email exists with googleplus'){
                        this.isPasswordVisible = false;
                        googlelink[0].click();
                    } else {
                        this.isPasswordVisible = true;
                    }
                } else {
                    console.log('SEDASDA');
                }
                $.ajax({
                    url: url.build('reclaim/checkout/email'),
                    method: 'POST',
                    data: {
                      'email': customer_email
                    },
                    success: function (data) {
                        self.validateEmail(customer_email);
                        console.log('Klaviyo_Reclaim - Quote updated with customer email: ' + customer_email);
                    },
                });
            }
        });
    },

  });
});
