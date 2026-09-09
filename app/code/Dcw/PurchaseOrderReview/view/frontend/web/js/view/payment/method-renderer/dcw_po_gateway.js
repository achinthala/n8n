define([
    'Magento_Checkout/js/view/payment/default',
    'jquery',
    'mage/validation',
    'mage/translate',
    'Magento_Ui/js/modal/modal'
], function (Component, $, validation, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Dcw_PurchaseOrderReview/payment/po_gateway',
            contactName: '',
            contactPhone: '',
            contactEmail: ''
        },

        /** @type {JQuery|null} */
        poShippingModalEl: null,

        initialize: function () {
            this._super();

            return this;
        },

        /**
         * Show custom-shipping message when shopper selects this method (including single-method checkout).
         */
        selectPaymentMethod: function () {
            var result = this._super();

            this.openPoShippingModal();

            return result;
        },

        initObservable: function () {
            this._super();
            this.observe(['contactName', 'contactPhone', 'contactEmail']);
            this.contactPhone.subscribe(function (value) {
                var digits = String(value || '').replace(/\D/g, '').substring(0, 10);

                if (digits !== String(value || '')) {
                    this.contactPhone(digits);
                }
            }, this);

            return this;
        },

        openPoShippingModal: function () {
            var bodyHtml,
                modalTitle,
                fromCms = window.checkoutConfig && window.checkoutConfig.dcwPoShippingModalHtml
                    ? String(window.checkoutConfig.dcwPoShippingModalHtml).trim()
                    : '',
                fromCmsTitle = window.checkoutConfig && window.checkoutConfig.dcwPoShippingModalTitle
                    ? String(window.checkoutConfig.dcwPoShippingModalTitle).trim()
                    : '';

            if (!this.poShippingModalEl) {
                bodyHtml = fromCms !== '' ? fromCms : (
                    '<p>' + $t('The total weight qualifies you for custom shipping rates!') + '</p>' +
                    '<p>' + $t('Let\'s make sure you get the best deal:') + '</p>' +
                    '<p>' + $t('Chat or call us at') +
                    ' <a href="tel:+18664166388">866-416-6388</a></p>' +
                    '<p>' + $t('Fast, simple, and built to save you more.') + '</p>'
                );
                modalTitle = fromCmsTitle !== '' ? fromCmsTitle : $t('Your Order Unlocks Custom Shipping');
                modalTitle = ''; // TODO: remove this once the client provides a title

                this.poShippingModalEl = $('<div/>').html(bodyHtml);
                this.poShippingModalEl.modal({
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'dcw-po-shipping-offer-modal',
                    title: modalTitle,
                    buttons: [{
                        text: $t('OK'),
                        class: 'action-primary',
                        click: function () {
                            this.closeModal();
                        }
                    }]
                });
            }

            this.poShippingModalEl.modal('openModal');
        },

        getData: function () {
            return {
                method: this.item.method,
                'additional_data': {
                    po_contact_name: this.contactName(),
                    po_contact_phone: this.contactPhone(),
                    po_contact_email: this.contactEmail()
                }
            };
        },

        validate: function () {
            var form = 'form[data-role=dcw-po-gateway-form]';

            return $(form).validation() && $(form).validation('isValid');
        }
    });
});
