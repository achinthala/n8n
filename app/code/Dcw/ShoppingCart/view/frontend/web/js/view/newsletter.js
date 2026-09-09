//@ts-check
/** @global {window.checkoutConfig} */
define([
  'jquery',
  'Magento_Ui/js/form/form',
  'Magento_Customer/js/customer-data'
], function($, Component, customerData) {
  'use strict';

  return Component.extend({
      defaults: {
          template: 'Dcw_ShoppingCart/checkout/newsletter'
      },
      isSubscribed: true,

      initialize: function () {
          this._super();

          // Set initial state based on customer preference
          if (!customerData.get('newsletter')().is_subscribed) {
            customerData.set('newsletter', {is_subscribed: true});
          }

          this.isSubscribed(customerData.get('newsletter')().is_subscribed);

          return this;
      },

      initObservable: function () {
        this._super();
        this.observe(['isSubscribed']);

        this.isSubscribed.subscribe(function (val) {
          customerData.set('newsletter', {is_subscribed: val});
        })

        return this;
    },
  });
});
