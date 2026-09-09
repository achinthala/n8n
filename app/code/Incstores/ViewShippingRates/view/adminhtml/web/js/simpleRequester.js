/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Simple Requester Module
 * Handles simple shipping rate requests including validation, data preparation, and API calls
 */

define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return {
        options: {},

        /**
         * Initialize the simple requester
         * @param {Object} config
         */
        initialize: function (config) {
            this.options = config;
            this.bindEvents();
            this.initializeFormState();
        },

        /**
         * Bind events specific to simple requests
         */
        bindEvents: function () {
            var self = this;

            // Real-time validation on weight input
            $('#total_weight').on('input', function () {
                self.validateWeight($(this));
            });

            // Weight class change handler
            $('#weight_class').on('change', function () {
                self.handleWeightClassChange($(this).val());
            });

            // ZIP code formatting and validation
            $('#ship_from_zipcode, #ship_to_zipcode').on('blur', function () {
                self.formatAndValidateZipCode($(this));
            });
        },

        /**
         * Initialize form state for simple requests
         */
        initializeFormState: function () {
            // Set required fields for simple requests
            this.setRequiredFields(['weight_class', 'total_weight', 'ship_from_zipcode', 'ship_to_zipcode']);

            // Initialize placeholders and tooltips
            this.initializeFieldHelpers();
        },

        /**
         * Set required fields
         * @param {Array} fields
         */
        setRequiredFields: function (fields) {
            // Remove required from all conditional fields first
            $('#simple-request-parameters input, #simple-request-parameters select').prop('required', false);

            // Add required to specified fields
            fields.forEach(function (field) {
                $('#' + field).prop('required', true);
            });
        },

        /**
         * Initialize field helpers and tooltips
         */
        initializeFieldHelpers: function () {
            // Add helpful placeholders
            // $('#ship_from_zipcode').attr('placeholder', 'e.g., 90210');
            // $('#ship_to_zipcode').attr('placeholder', 'e.g., 10001');
            // $('#total_weight').attr('placeholder', 'Enter weight in lbs');
            //
            // // Add weight class helper text
            // $('#weight_class').after('<div class="field-note"><small>' +
            //     $t('Weight class determines freight classification. Higher classes typically mean denser items.') +
            //     '</small></div>');
        },

        /**
         * Validate weight input
         * @param {jQuery} $field
         * @returns {boolean}
         */
        validateWeight: function ($field) {
            var weight = $field.val();
            var isValid = true;
            var errorMessage = '';

            // Remove previous error styling
            $field.removeClass('mage-error');
            $field.next('.field-error').remove();

            if (weight !== '') {
                if (!$.isNumeric(weight)) {
                    isValid = false;
                    errorMessage = $t('Weight must be a number');
                } else {
                    var numericWeight = parseFloat(weight);
                    if (numericWeight <= 0) {
                        isValid = false;
                        errorMessage = $t('Weight must be greater than 0');
                    } else if (numericWeight > 50000) {
                        isValid = false;
                        errorMessage = $t('Weight seems too high. Please verify.');
                    }
                }
            }

            if (!isValid) {
                $field.addClass('mage-error');
                $field.after('<div class="field-error" style="color: red; font-size: 12px; margin-top: 2px;">' +
                    errorMessage + '</div>');
            }

            return isValid;
        },

        /**
         * Handle weight class change
         * @param {string} weightClass
         */
        handleWeightClassChange: function (weightClass) {
            if (!weightClass) return;

            // Extract class number for helper information
            var classMatch = weightClass.match(/CLASS_(\d+(?:_\d+)?)/);
            if (classMatch) {
                var classNumber = classMatch[1].replace('_', '.');
                var helperText = $t('Class %1 - ').replace('%1', classNumber);

                // Add context based on class range
                if (parseFloat(classNumber) <= 70) {
                    helperText += $t('Heavy, dense items');
                } else if (parseFloat(classNumber) <= 100) {
                    helperText += $t('Medium density items');
                } else {
                    helperText += $t('Light, bulky items');
                }

                // Update or add helper text
                var $helper = $('#weight_class').siblings('.field-note');
                if ($helper.length) {
                    $helper.find('small').text(helperText);
                }
            }
        },

        /**
         * Format and validate ZIP code
         * @param {jQuery} $field
         * @returns {boolean}
         */
        formatAndValidateZipCode: function ($field) {
            var zipCode = $field.val().replace(/\D/g, ''); // Remove non-digits
            var isValid = true;
            var errorMessage = '';

            // Remove previous error styling
            $field.removeClass('mage-error');
            $field.next('.field-error').remove();

            if (zipCode !== '') {
                if (zipCode.length === 5) {
                    $field.val(zipCode);
                } else if (zipCode.length === 9) {
                    $field.val(zipCode.substring(0, 5) + '-' + zipCode.substring(5));
                } else {
                    isValid = false;
                    errorMessage = $t('ZIP code must be 5 or 9 digits');
                }
            }

            if (!isValid) {
                $field.addClass('mage-error');
                $field.after('<div class="field-error" style="color: red; font-size: 12px; margin-top: 2px;">' +
                    errorMessage + '</div>');
            }

            return isValid;
        },

        /**
         * Validate simple request form
         * @param {Object} formData
         * @returns {boolean}
         */
        validateForm: function (formData) {
            var isValid = true;
            var errors = [];

            // Validate required fields
            var requiredFields = {
                'ship_to_zipcode': $t('Ship to ZIP code is required'),
                'ship_from_zipcode': $t('Ship from ZIP code is required'),
                'weight_class': $t('Weight class is required'),
                'total_weight': $t('Total weight is required')
            };

            Object.keys(requiredFields).forEach(function (field) {
                var fieldValue = formData[field];
                
                if (!fieldValue || fieldValue.trim() === '') {
                    isValid = false;
                    errors.push(requiredFields[field]);
                    $('#' + field).addClass('mage-error');
                } else {
                    $('#' + field).removeClass('mage-error');
                }
            });

            // Validate weight
            if (formData.total_weight) {
                if (!this.validateWeight($('#total_weight'))) {
                    isValid = false;
                }
            }

            // Validate ZIP codes
            if (formData.ship_from_zipcode) {
                if (!this.formatAndValidateZipCode($('#ship_from_zipcode'))) {
                    isValid = false;
                }
            }
            if (formData.ship_to_zipcode) {
                if (!this.formatAndValidateZipCode($('#ship_to_zipcode'))) {
                    isValid = false;
                }
            }

            // Show validation errors
            if (!isValid && errors.length > 0) {
                this.showValidationErrors(errors);
            }

            return isValid;
        },

        /**
         * Show validation errors
         * @param {Array} errors
         */
        showValidationErrors: function (errors) {
            var errorMessage = '<ul><li>' + errors.join('</li><li>') + '</li></ul>';
            this.showMessage($t('Please fix the following errors:') + errorMessage, 'error');
        },

        /**
         * Prepare request data for simple requests
         * @param {Object} formData
         * @returns {Object}
         */
        prepareRequestData: function (formData) {
            return {
                request_type: 'simple',
                ship_to_zipcode: formData.ship_to_zipcode,
                ship_to_street: formData.ship_to_street || '',
                ship_to_city: formData.ship_to_city || '',
                ship_to_state: formData.ship_to_state || '',
                ship_from_zipcode: formData.ship_from_zipcode,
                ship_from_street: formData.ship_from_street || '',
                ship_from_city: formData.ship_from_city || '',
                ship_from_state: formData.ship_from_state || '',
                weight_class: formData.weight_class,
                total_weight: parseInt(formData.total_weight),
                residential: formData.residential ? '1' : '',
                lift_gate_required: formData.lift_gate_required ? '1' : '',
                delivery_notification: formData.delivery_notification ? '1' : '',
                transaction_id: formData.transaction_id || ''
            };
        },

        /**
         * Submit simple request
         * @param {Object} formData
         * @param {Function} onSuccess
         * @param {Function} onError
         */
        submitRequest: function (formData, onSuccess, onError) {
            var self = this;

            // Validate form first
            if (!this.validateForm(formData)) {
                // Call onError to ensure loading state is reset
                onError($t('Please fix validation errors before submitting'));
                return;
            }

            // Prepare request data
            var requestData = this.prepareRequestData(formData);

            // Make API call
            $.ajax({
                url: this.options.getSimpleRatesUrl,
                type: 'POST',
                data: requestData,
                success: function (response) {
                    if (response.success) {
                        onSuccess(response.data);
                    } else {
                        onError(response.message || $t('An error occurred'));
                    }
                },
                error: function (xhr, status, error) {
                    var errorMessage = $t('Failed to get shipping rates. Please try again.');
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    onError(errorMessage);
                }
            });
        },

        /**
         * Show message to user
         * @param {string} message
         * @param {string} type
         */
        showMessage: function (message, type) {
            type = type || 'notice';

            // Remove existing messages
            $('.shipping-rates-message').remove();

            var messageHtml = '<div class="message message-' + type + ' shipping-rates-message">' +
                '<div>' + message + '</div>' +
                '</div>';

            // Find the correct container - use the form container or page section
            var $container = $('.admin__page-section-content').first();
            if ($container.length === 0) {
                $container = $('#shipping-rates-form').parent();
            }
            
            $container.prepend(messageHtml);

            // Auto-hide after 5 seconds
            setTimeout(function () {
                $('.shipping-rates-message').fadeOut();
            }, 5000);
        }
    };
});
