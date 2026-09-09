/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
  'ko',
  'jquery',
  'uiComponent',
  'Magento_CheckoutAgreements/js/model/agreements-modal',
  'uiRegistry'
], function (ko, $, Component, agreementsModal, uiRegistry) {
  'use strict';
  jQuery.fn.toggleAttr = function (attr) {
    return this.each(function () {
      var $this = $(this);
      $this.attr(attr) ? $this.removeAttr(attr) : $this.attr(attr, attr);
    });
  };
  var checkoutConfig = window.checkoutConfig,
    agreementManualMode = 1,
    agreementsConfig = checkoutConfig ? checkoutConfig.checkoutAgreements : {};


  return Component.extend({
    defaults: {
      template: 'Magento_CheckoutAgreements/checkout/checkout-agreements'
    },
    isVisible: agreementsConfig.isEnabled,
    agreements: agreementsConfig.agreements,
    modalTitle: ko.observable(null),
    modalContent: ko.observable(null),
    contentHeight: ko.observable(null),
    modalWindow: null,
    isAgreementAccepted: ko.observable(false),

    /**
     * Checks if agreement required
     *
     * @param {Object} element
     */
    isAgreementRequired: function (element) {
      return element.mode == agreementManualMode; //eslint-disable-line eqeqeq
    },

    /**
     * Show agreement content in modal
     *
     * @param {Object} element
     */
    showContent: function (element) {
      this.modalTitle(element.checkboxText);
      this.modalContent(element.content);
      this.contentHeight(element.contentHeight ? element.contentHeight : 'auto');
      agreementsModal.showModal();
    },

    /**
     * build a unique id for the term checkbox
     *
     * @param {Object} context - the ko context
     * @param {Number} agreementId
     */
    getCheckboxId: function (context, agreementId) {
      var paymentMethodName = '',
        paymentMethodRenderer = context.$parents[1];

      // corresponding payment method fetched from parent context
      if (paymentMethodRenderer) {
        // item looks like this: {title: "Check / Money order", method: "checkmo"}
        paymentMethodName = paymentMethodRenderer.item ?
          paymentMethodRenderer.item.method : '';
      }
      let buttons = $('.checkout-index-index.payment-step #maincontent .payment-methods .payment-group .all-payments-container .payment-method .payment-method-content button.action.primary.checkout,#amazon-payment .amazon_payment_v2-label');

      // setTimeout(() => {
      //   paymentMethodRenderer.isPlaceOrderActionAllowed(false);
      //   buttons.each(function () {
      //     $(this).addClass('disabled');
      //   });
      // }, 1000);

      return 'agreement_' + paymentMethodName + '_' + agreementId;
    },

    /**
     * Init modal window for rendered element
     *
     * @param {Object} element
     */
    initModal: function (element) {
      agreementsModal.createModal(element);
    },

    /**
     * Custom function
     */
    placeOrderToggle: function (parent) {
      let button = $('.checkout-index-index.payment-step #maincontent .payment-methods .payment-group .all-payments-container .payment-method .payment-method-content button.action.primary.checkout, #amazon-payment .amazon_payment_v2-label');
      let self = this;
      button.each(function () {
        var isPaypal = false;
        var method = false;
        if (parent.$parentContext.$parents[1] && parent.$parentContext.$parents[1].index == 'paypal_express') {
          isPaypal = true;
        }
        if (parent.$parentContext.method) {
          method = parent.$parentContext.method;
        }
        if (jQuery('input[name="billing-address-same-as-shipping"]').is(':checked')) {
          if (method.item.method == 'authnetcim') {
			   var selectedCard = false;
			  
               var isValid = true;
			   
			   if ($("#authnetcim-card-id").length > 0) {
				   if($("#authnetcim-card-id").val()){
					   selectedCard = true;
				   }
				}
				if(!selectedCard){

                    // Credit Card Number
                    var ccNumber = $('#authnetcim-cc-number').val();
                    ccNumber = ccNumber.replace(/\s/g, '');
                    if (!ccNumber || ccNumber.length > 16 || ccNumber.length < 16) {
                        isValid = false;
                    }

                    // Expiration Month
                    var expMonth = $('#authnetcim-cc-exp-month').val();
                    if (!expMonth || expMonth < 1 || expMonth > 12) {
                        isValid = false;
                    }

                    // Expiration Year
                    var expYear = $('#authnetcim-cc-exp-year').val();
                    var currentYear = new Date().getFullYear();
                    if (!expYear || expYear < currentYear) {
                        isValid = false;
                    }

                    // Check if card is expired
                    if (expYear == currentYear) {
                        var currentMonth = new Date().getMonth() + 1;
                        if (expMonth < currentMonth) {
                            isValid = false;
                        }
                    }
                    // CVV (if visible)
                    var cvvField = $('#authnetcim-cc-cid');
                    if ( (!cvvField.val() || cvvField.val().length < 3 || cvvField.val().length > 3) ) {
                        isValid = false;
                    }
				}
            if (isValid) {
              $(this).removeClass('disabled');
              $(this).removeAttr('disabled');
               $('#authnetcim-submit').removeClass('disabled').removeAttr('disabled');
            }
          } else {
            $(this).removeClass('disabled');
            $(this).removeAttr('disabled');
          }


          method && method.isPlaceOrderActionAllowed(true);

          if (isPaypal) {
            jQuery('#amazon-payment .checkout-agreement input[type="checkbox"]').prop('checked', true);
            jQuery('#amazon-payment .checkout-agreement input[type="checkbox"]').change();
          }
        } else {
          $(this).addClass('disabled');
          $(this).attr('disabled', '');

          method && method.isPlaceOrderActionAllowed(false);

          if (isPaypal) {
            jQuery('#amazon-payment .checkout-agreement input[type="checkbox"]').prop('checked', false);
            jQuery('#amazon-payment .checkout-agreement input[type="checkbox"]').change();
          }
        }
      });
    }
  });
});