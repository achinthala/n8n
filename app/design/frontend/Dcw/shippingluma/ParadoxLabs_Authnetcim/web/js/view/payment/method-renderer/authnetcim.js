/**
 * Copyright © 2015-present ParadoxLabs, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Need help? Try our knowledgebase and support system:
 * @link https://support.paradoxlabs.com
 */

define(
    [
        'ko',
        'jquery',
        'ParadoxLabs_TokenBase/js/view/payment/method-renderer/cc',
        'mage/translate',
         'uiRegistry'
    ],

    function (ko, $, Component, $t, registry) {
        'use strict';
        var config = window.checkoutConfig.payment.authnetcim;
        return Component.extend({
            defaults: {
                template: 'ParadoxLabs_Authnetcim/payment/cc',
                save: config ? config.canSaveCard && config.defaultSaveCard : false,
                selectedCard: config ? config.selectedCard : '',
                storedCards: config ? config.storedCards : {},
                creditCardExpMonth: config ? config.creditCardExpMonth : null,
                creditCardExpYear: config ? config.creditCardExpYear : null,
                logoImage: config ? config.logoImage : false,
                apiLoginId: config ? config.apiLoginId : '',
                clientKey: config ? config.clientKey : '',
                sandbox: config ? config.sandbox : false,
                canStoreBin: config ? config.canStoreBin : false,
            },

            // Change certain error responses to be more useful.
            errorMap: {
                'E_WC_10': 'API credentials invalid. If you are an administrator, please correct the API Login ID.',
                'E_WC_19': 'API credentials invalid. If you are an administrator, please correct the API Login ID and'
                    + ' Client Key.',
                'E_WC_21': 'API credentials invalid. If you are an administrator, please correct the API Login ID and'
                    + ' Client Key.'
            },

            initVars: function () {
                this.canSaveCard = config ? config.canSaveCard : false;
                this.forceSaveCard = config ? config.forceSaveCard : false;
                this.defaultSaveCard = config ? config.defaultSaveCard : false;
                this.requireCcv = config ? config.requireCcv : false;
            },

            /**
             * @override
             */
            initObservable: function () {
                this.initVars();
                this._super()
                    .observe([
                        'acceptJsKey',
                        'acceptJsValue',
                        'creditCardLast4',
                        'creditCardBin',
                        'canStoreBin'
                    ]);

                this.placeOrderFailure.subscribe(this.clearToken.bind(this));

                if (this.useAcceptJs()) {
                    if (this.sandbox) {
                        require(
                            ['authorizeNetAcceptjsSandbox'],
                            this.initAcceptJs.bind(this)
                        );
                    } else {
                        require(
                            ['authorizeNetAcceptjs'],
                            this.initAcceptJs.bind(this)
                        );
                    }
                }
                $(document).on('keyup change', '#authnetcim-cc-number, #authnetcim-cc-exp-month, #authnetcim-cc-exp-year, #authnetcim-cc-cid', function () {
                    var isValid = true;

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
                    if ( (!cvvField.val() || cvvField.val().length < 3 || cvvField.val().length > 3)) {
                        isValid = false;
                    }
                   var billingCheckbox =   jQuery('input[name="billing-address-same-as-shipping"]').is(':checked')

                   var agreementsComponent = registry.get('checkout.steps.billing-step.payment.payments-list.before-place-order.agreements');
                   var isTermActive = agreementsComponent ? agreementsComponent.isAgreementAccepted() : false;
                    if (isValid) {
                      $('#authnetcim-submit').removeClass('disabled').removeAttr('disabled');
                    }
                    else {
                     $('#authnetcim-submit').removeClass('disabled').removeAttr('disabled');
                     
                    }
                });

                return this;
            },

            initAcceptJs: function () {
                window[this.item.method + '_acceptJs_callback'] = this.handlePaymentResponse.bind(this);
                window.isReady = true;
            },

            clearToken: function (placeOrderFailure) {
                if (placeOrderFailure === true) {
                    this.acceptJsKey(null);
                    this.acceptJsValue(null);
                }
            },

            /**
             * @override
             */
            getData: function () {
                var paymentData = this._super();

                if (this.useAcceptJs()) {
                    delete paymentData.additional_data.cc_number;

                    $.extend(
                        true,
                        paymentData,
                        {
                            'additional_data': {
                                'acceptjs_key': this.acceptJsKey(),
                                'acceptjs_value': this.acceptJsValue(),
                                'cc_last4': this.creditCardLast4(),
                                'cc_bin': this.creditCardBin()
                            }
                        }
                    )
                }

                return paymentData;
            },

            getApiLoginId: function () {
                return this.apiLoginId !== null ? this.apiLoginId : '';
            },

            getClientKey: function () {
                return this.clientKey !== null ? this.clientKey : '';
            },

            getSandbox: function () {
                return this.sandbox !== null ? this.sandbox : false;
            },

            useAcceptJs: function () {
                return this.getApiLoginId().length > 0 && this.getClientKey().length > 0;
            },

            handlePaymentResponse: function (response) {
                if (response.messages.resultCode === 'Error') {
                    this.handlePaymentResponseError(response);
                } else {
                    this.handlePaymentResponseSuccess(response);
                }
            },

            handlePaymentResponseError: function (response) {
                this.acceptJsKey(null);
                this.acceptJsValue(null);
				console.log('handlePaymentResponseError1='+JSON.stringify(response));

                var messages = [];
                for (var i = 0; i < response.messages.message.length; i++) {
                    var errorText = response.messages.message[i].text;
                    if (typeof this.errorMap[response.messages.message[i].code] !== 'undefined') {
                        errorText = this.errorMap[response.messages.message[i].code];
                    }
					if(response.messages.message[i].code == 'E_WC_08'){
						messages.push(
                        $.mage.__('Expiration date is incorrect.')
                    );
					}else if(response.messages.message[i].code == 'E_WC_05'){
						messages.push(
                        $.mage.__('Card number is invalid.')
                    );
					}else if(response.messages.message[i].code == 'E_WC_15'){
						messages.push(
                        $.mage.__('CVV is incorrect.')
                    );
					}else{
                    messages.push(
                        $.mage.__('Payment Error: %1 <small><em>(%2)</em></small>')
                            .replace('%1', $.mage.__(errorText))
                            .replace('%2', response.messages.message[i].code)
                    );
					}
                }

                this.isTokenizing(false);

                this.handleFailedOrder({
                    responseText: JSON.stringify({
                        message: messages.join("\n")
                    })
                });
            },

            handlePaymentResponseSuccess: function (response) {
                this.acceptJsKey(response.opaqueData.dataDescriptor);
                this.acceptJsValue(response.opaqueData.dataValue);

                this.isTokenizing(false);

                this.placeOrder();
            },

            placeOrder: function (data, event) {

                if (this.selectedCard() || this.acceptJsValue() || !this.useAcceptJs()) {
                    return this._super(data, event);
                } else {
                    this.isTokenizing(true);

                    var cc_no = this.creditCardNumber().replace(/\D/g, '');
                    this.creditCardLast4(cc_no.substring(cc_no.length - 4));
                    if (this.canStoreBin) {
                        this.creditCardBin(cc_no.substring(0, 6));
                    }

                    var paymentData = {
                        cardData: {
                            cardNumber: cc_no,
                            month: this.creditCardExpMonth(),
                            year: this.creditCardExpYear(),
                            cardCode: this.creditCardVerificationNumber() ? this.creditCardVerificationNumber() : ''
                        },
                        authData: {
                            clientKey: this.clientKey,
                            apiLoginID: this.apiLoginId
                        }
                    };

                    Accept.dispatchData(
                        paymentData,
                        this.item.method + '_acceptJs_callback'
                    );
                }

                return false;
            }
        });
    }
);