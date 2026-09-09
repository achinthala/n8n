//@ts-check
/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Dcw_SplitPayment/js/action/set-payment-method-action',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data'
    ],
    function (ko, $, Component, setPaymentMethodAction, additionalValidators, selectPaymentMethodAction, checkoutData) {
        'use strict';
        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'Dcw_SplitPayment/payment/splitpayment'
            },
            /**
             * Initialize view.
             *
             * @return {exports}
             */
            initialize: function () {
              this._super();

              this.isPlaceOrderActionAllowed(additionalValidators.validate());

              setInterval(() => {
                if (this.isChecked()) {
                  this.isPlaceOrderActionAllowed(additionalValidators.validate());
                }
              }, 500);

              return this;
            },
            afterPlaceOrder: function () {
                setPaymentMethodAction(this.messageContainer);
                return false;
            },

            /**
             * @return {Boolean}
             */
            selectPaymentMethod: function () {
              selectPaymentMethodAction(this.getData());
              checkoutData.setSelectedPaymentMethod(this.item.method);

              this.isPlaceOrderActionAllowed(additionalValidators.validate());

              return true;
          },
        });
    }
);