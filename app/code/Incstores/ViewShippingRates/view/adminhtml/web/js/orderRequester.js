/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Order Requester Module
 * Handles order shipping rate requests including validation, data preparation, and API calls
 */

define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return {
        options: {},
        currentRequestType: null,
        pendingOrderRequest: null,

        /**
         * Initialize the order requester
         * @param {Object} config
         */
        initialize: function (config) {
            this.options = config;
            this.bindEvents();
        },

        /**
         * Bind events specific to order requests
         */
        bindEvents: function () {
            var self = this;

            // Transaction ID validation (for orders when active)
            $('#transaction_id').on('blur', function () {
                if (self.currentRequestType === 'order') {
                    self.validateOrderId($(this));
                }
            });

            // Address selection change handler
            $('#selected_address_id').on('change', function () {
                self.handleAddressSelection($(this));
            });

            // Use selected address button handler
            $('#use-selected-address-btn').on('click', function (e) {
                e.preventDefault();
                self.submitWithSelectedAddress();
            });
        },

        /**
         * Initialize form state for order request type
         */
        initializeFormState: function () {
            this.currentRequestType = 'order';
            this.initializeOrderRequest();
        },

        /**
         * Initialize order request form
         */
        initializeOrderRequest: function () {
            // Set required fields - transaction_id is always required
            this.setRequiredFields(['transaction_id']);

            // Initialize order search
            this.initializeOrderSearch();

            // Add field helpers
            this.initializeOrderFieldHelpers();
        },

        /**
         * Set required fields
         * @param {Array} fieldIds
         */
        setRequiredFields: function (fieldIds) {
            fieldIds.forEach(function (fieldId) {
                var field = $('#' + fieldId);
                if (field.length) {
                    field.prop('required', true);
                    field.closest('.admin__field').addClass('_required');
                }
            });
        },

        /**
         * Initialize order search autocomplete
         */
        initializeOrderSearch: function () {
            var self = this;
            var orderField = $('#transaction_id');

            if (orderField.length && this.options.getOrderUrl) {
                orderField.autocomplete({
                    source: function (request, response) {
                        $.ajax({
                            url: self.options.getOrderUrl,
                            data: {
                                term: request.term,
                                action: 'search'
                            },
                            dataType: 'json',
                            success: function (data) {
                                if (data.success && data.data) {
                                    response(data.data);
                                } else {
                                    response([]);
                                }
                            },
                            error: function () {
                                response([]);
                            }
                        });
                    },
                    minLength: 2,
                    select: function (event, ui) {
                        if (ui.item) {
                            $(this).val(ui.item.incrementId || ui.item.value);
                            self.validateOrderId($(this));
                            return false;
                        }
                    }
                });
            }
        },

        /**
         * Initialize order field helpers
         */
        initializeOrderFieldHelpers: function () {
            var orderField = $('#transaction_id');
            
            if (orderField.length && !orderField.next('.field-note').length) {
                orderField.after('<div class="field-note">Enter order ID or increment ID (order number)</div>');
            }
        },

        /**
         * Validate order ID field
         * @param {jQuery} field
         * @returns {boolean}
         */
        validateOrderId: function (field) {
            var value = field.val().trim();
            var isValid = value.length > 0;

            this.clearFieldError(field);

            if (!isValid) {
                this.showFieldError(field, $t('Order ID is required'));
                return false;
            }

            return true;
        },

        /**
         * Handle address selection change
         * @param {jQuery} select
         */
        handleAddressSelection: function (select) {
            var selectedValue = select.val();
            var useButton = $('#use-selected-address-btn');

            if (selectedValue && selectedValue !== '') {
                useButton.prop('disabled', false).removeClass('disabled');
            } else {
                useButton.prop('disabled', true).addClass('disabled');
            }
        },

        /**
         * Submit order request with selected address
         */
        submitWithSelectedAddress: function () {
            var selectedAddressId = $('#selected_address_id').val();
            var currentFormData = this.getFormData();

            if (!selectedAddressId) {
                if (this.pendingOrderRequest && this.pendingOrderRequest.onError) {
                    this.pendingOrderRequest.onError($t('Please select an address'));
                }
                return;
            }

            if (!this.pendingOrderRequest) {
                console.error('No pending order request found');
                return;
            }

            // Add selected address to form data and resubmit with stored callbacks
            currentFormData.selected_address_id = selectedAddressId;
            this.submitRequest(currentFormData, this.pendingOrderRequest.onSuccess, this.pendingOrderRequest.onError);
        },

        /**
         * Get current form data
         * @returns {Object}
         */
        getFormData: function () {
            return {
                order_id: $('#transaction_id').val().trim(),
                transaction_id: $('#transaction_id').val() || 'order-' + new Date().getTime(),
                residential: $('#residential').is(':checked'),
                lift_gate_required: $('#lift_gate_required').is(':checked'),
                delivery_notification: $('#delivery_notification').is(':checked')
            };
        },

        /**
         * Validate order request
         * @param {Object} formData - optional form data to validate
         * @returns {Object} validation result with isValid boolean and errors array
         */
        validateRequest: function (formData) {
            var errors = [];
            var orderId;
            
            if (formData && formData.transaction_id) {
                // Use transaction_id from form data
                orderId = formData.transaction_id.trim();
            } else {
                // Fall back to checking the DOM
                orderId = $('#transaction_id').val() ? $('#transaction_id').val().trim() : '';
            }

            // Validate order ID
            if (!orderId) {
                errors.push($t('Order ID is required'));
                this.showFieldError($('#transaction_id'), $t('Order ID is required'));
            }

            return {
                isValid: errors.length === 0,
                errors: errors
            };
        },

        /**
         * Submit order shipping request
         * @param {Object} formData
         * @param {Function} onSuccess
         * @param {Function} onError
         */
        submitRequest: function (formData, onSuccess, onError) {
            var self = this;

            if (!formData || !formData.transaction_id) {
                formData = this.getFormData();
            } else {
                // Ensure formData has order_id field for backend compatibility
                if (formData.transaction_id && !formData.order_id) {
                    formData.order_id = formData.transaction_id;
                }
            }

            // Validate request
            var validation = this.validateRequest(formData);
            if (!validation.isValid) {
                if (onError) {
                    onError($t('Please correct the errors and try again'));
                }
                return;
            }

            // Submit to GetOrderRates endpoint
            console.log('=== ORDER REQUEST ===');
            console.log('Request URL:', this.options.getOrderRatesUrl);
            console.log('Request Data:', formData);
            
            $.ajax({
                url: this.options.getOrderRatesUrl,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    console.log('=== ORDER RESPONSE ===');
                    console.log('Full Response:', response);
                    if (response.success && response.data) {
                        console.log('Order Data:', response.data.orderInfo || 'No orderInfo in response');
                        console.log('Shipping Rates:', response.data.data || response.data);
                    }
                    self.handleResponse(response, onSuccess, onError);
                },
                error: function (xhr, status, error) {
                    console.error('Order rates request failed:', error);
                    console.error('XHR Response:', xhr.responseText);
                    if (onError) {
                        onError($t('Request failed. Please try again.'));
                    }
                }
            });
        },

        /**
         * Handle API response
         * @param {Object} response
         * @param {Function} onSuccess
         * @param {Function} onError
         */
        handleResponse: function (response, onSuccess, onError) {
            if (response.success) {
                if (response.requiresAddressSelection) {
                    // Store callbacks for later use when address is selected
                    this.pendingOrderRequest = {
                        onSuccess: onSuccess,
                        onError: onError,
                        orderData: response.orderData
                    };
                    this.showAddressSelection(response.orderData);
                } else {
                    // Call success callback with response data
                    if (onSuccess) {
                        onSuccess(response.data);
                    }
                }
            } else {
                // Log debug info if available
                if (response.debugInfo) {
                    console.log('Order request debug info:', response.debugInfo);
                }
                
                if (onError) {
                    onError(response.message || $t('Request failed'));
                }
            }
        },

        /**
         * Show address selection interface
         * @param {Object} orderData
         */
        showAddressSelection: function (orderData) {
            var addressContainer = $('#address-selection-container');
            var addressSelect = $('#selected_address_id');

            if (orderData.customerAddresses && orderData.customerAddresses.addresses) {
                // Clear existing options
                addressSelect.empty().append('<option value="">Select an address...</option>');

                // Add address options
                orderData.customerAddresses.addresses.forEach(function (address) {
                    var label = address.street + ', ' + address.city + ', ' + address.region + ' ' + address.postcode;
                    addressSelect.append('<option value="' + address.id + '">' + label + '</option>');
                });

                // Show address selection
                addressContainer.show();
            } else {
                console.error('Order has no shipping address and no customer addresses are available');
            }
        },


        /**
         * Show field error
         * @param {jQuery} field
         * @param {string} message
         */
        showFieldError: function (field, message) {
            field.addClass('admin__field-error');
            
            var errorDiv = field.next('.admin__field-error');
            if (errorDiv.length === 0) {
                errorDiv = $('<div class="admin__field-error"></div>');
                field.after(errorDiv);
            }
            errorDiv.text(message);
        },

        /**
         * Clear field error
         * @param {jQuery} field
         */
        clearFieldError: function (field) {
            field.removeClass('admin__field-error');
            field.next('.admin__field-error').remove();
        }
    };
});