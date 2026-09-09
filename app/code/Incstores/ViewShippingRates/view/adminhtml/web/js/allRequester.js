/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * All Requester Module
 * Handles product and quote shipping rate requests including validation, data preparation, and API calls
 */


define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';



    return {
        options: {},
        currentRequestType: null,

        /**
         * Initialize the all requester
         * @param {Object} config
         */
        initialize: function (config) {
            this.options = config;
            this.bindEvents();
        },

        /**
         * Bind events specific to product/quote requests
         */
        bindEvents: function () {
            var self = this;

            // Product selection change handler
            $('#product_name').on('change', function () {
                self.handleProductChange($(this).val());
            });

            // SKU selection change handler
            $('#sku').on('change', function () {
                self.handleSkuChange($(this).find(':selected'));
            });

            // Quantity input validation
            $('#quantity').on('input', function () {
                self.validateQuantity($(this));
            });

            // Unit weight input validation
            $('#unit_weight').on('input', function () {
                self.validateUnitWeight($(this));
            });

            // Transaction ID validation (for quotes when active)
            $('#transaction_id').on('blur', function () {
                if (self.currentRequestType === 'quote') {
                    self.validateQuoteId($(this));
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
         * Initialize form state for specific request type
         * @param {string} requestType - 'product' or 'quote'
         */
        initializeFormState: function (requestType) {
            this.currentRequestType = requestType;

            if (requestType === 'product') {
                this.initializeProductRequest();
            } else if (requestType === 'quote') {
                this.initializeQuoteRequest();
            }
        },

        /**
         * Initialize product request form
         */
        initializeProductRequest: function () {
            // Set required fields
            this.setRequiredFields(['product_name', 'sku', 'unit_weight', 'quantity']);

            // Initialize product search
            this.initializeProductSearch();

            // Add field helpers
            this.initializeProductFieldHelpers();
        },

        /**
         * Initialize quote request form
         */
        initializeQuoteRequest: function () {
            // Set required fields - transaction_id is always required
            this.setRequiredFields(['transaction_id']);

            // Add field helpers
            this.initializeQuoteFieldHelpers();
        },

        /**
         * Set required fields
         * @param {Array} fields
         */
        setRequiredFields: function (fields) {
            // Remove required from all conditional fields first
            $('#product-request-parameters input, #product-request-parameters select, #quote-request-parameters input').prop('required', false);

            // Add required to specified fields
            fields.forEach(function (field) {
                $('#' + field).prop('required', true);
            });
        },

        /**
         * Initialize product search functionality
         */
        initializeProductSearch: function () {
            var self = this;
            var searchTimeout;
            var lastSearchTerm = '';

            // Convert product dropdown to searchable input
            var $productSelect = $('#product_name');
            var $container = $productSelect.parent();

            // Create search input
            var $searchInput = $('<input type="text" id="product_search" placeholder="' + $t('Type to search products...') + '" class="admin__control-text" style="width: 100%; margin-bottom: 5px;">');

            // Create results dropdown
            var $resultsDropdown = $('<select id="product_name_results" class="admin__control-select" size="8" style="width: 100%; display: none;">');

            // Insert elements
            $container.prepend($searchInput);
            $productSelect.after($resultsDropdown);
            $productSelect.hide(); // Hide original select

            // Load initial products
            self.loadProducts('');

            // Implement real-time search
            $searchInput.on('input', function () {
                var searchTerm = $(this).val().toLowerCase();

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    if (searchTerm !== lastSearchTerm) {
                        lastSearchTerm = searchTerm;
                        if (searchTerm.length >= 2 || searchTerm === '') {
                            self.loadProducts(searchTerm);
                        } else {
                            $resultsDropdown.hide();
                        }
                    }
                }, 300); // 300ms debounce
            });

            // Show/hide results on focus/blur
            $searchInput.on('focus', function () {
                if ($resultsDropdown.find('option').length > 0) {
                    $resultsDropdown.show();
                }
            });

            $searchInput.on('blur', function () {
                // Delay hiding to allow for selection
                setTimeout(function () {
                    $resultsDropdown.hide();
                }, 200);
            });

            // Handle result selection
            $resultsDropdown.on('change', function () {
                var selectedOption = $(this).find(':selected');
                if (selectedOption.length > 0) {
                    var productId = selectedOption.val();
                    var productLabel = selectedOption.text();

                    // Update search input with selected product name
                    $searchInput.val(productLabel);

                    // Update hidden original select
                    $productSelect.html('<option value="' + productId + '" selected>' + productLabel + '</option>');
                    $productSelect.val(productId).trigger('change');

                    $resultsDropdown.hide();
                }
            });

            // Handle double-click for quick selection
            $resultsDropdown.on('dblclick', 'option', function () {
                $(this).parent().val($(this).val()).trigger('change');
            });
        },

        /**
         * Initialize field helpers for product requests
         */
        initializeProductFieldHelpers: function () {
            $('#quantity').attr('placeholder', 'Enter quantity');
            $('#unit_weight').attr('placeholder', 'Weight per unit in lbs');

            // Add helpful notes
            $('#product_name').after('<div class="field-note"><small>' +
                $t('Search for configurable products by name or SKU') +
                '</small></div>');
        },

        /**
         * Initialize field helpers for quote requests
         */
        initializeQuoteFieldHelpers: function () {
            $('#transaction_id').attr('placeholder', 'Enter Amasty Quote ID');

            // Remove any existing field notes first
            $('#transaction_id').parent().find('.field-note').remove();

            // Hide address selection initially
            $('#address-selection-container').hide();
        },

        /**
         * Handle address selection change
         * @param {jQuery} $select
         */
        handleAddressSelection: function ($select) {
            var selectedValue = $select.val();
            var $preview = $('#selected-address-preview');
            var $content = $('#address-preview-content');
            var $button = $('#use-selected-address-btn');

            if (selectedValue && this.customerAddresses) {
                // Find selected address
                var selectedAddress = null;
                for (var i = 0; i < this.customerAddresses.length; i++) {
                    if (this.customerAddresses[i].id == selectedValue) {
                        selectedAddress = this.customerAddresses[i];
                        break;
                    }
                }

                if (selectedAddress) {
                    // Format address for preview
                    var addressText = selectedAddress.street + '\n' +
                        selectedAddress.city + ', ' + selectedAddress.region + ' ' + selectedAddress.postcode + '\n' +
                        selectedAddress.country_id;

                    $content.text(addressText);
                    $preview.show();
                    $button.show(); // Show the "Use Selected Address" button
                } else {
                    $preview.hide();
                    $button.hide();
                }
            } else {
                $preview.hide();
                $button.hide();
            }
        },

        /**
         * Show address selection UI
         * @param {Array} addresses
         */
        showAddressSelection: function (addresses) {
            var $select = $('#selected_address_id');
            var $container = $('#address-selection-container');

            // Store addresses for reference
            this.customerAddresses = addresses;

            // Clear existing options
            $select.find('option:not(:first)').remove();

            // Add address options
            for (var i = 0; i < addresses.length; i++) {
                var address = addresses[i];
                var option = $('<option></option>')
                    .attr('value', address.id)
                    .text(address.display_name);
                $select.append(option);
            }

            // Show the container
            $container.show();

            console.log('Address selection shown with ' + addresses.length + ' addresses');
        },

        /**
         * Hide address selection UI
         */
        hideAddressSelection: function () {
            $('#address-selection-container').hide();
            $('#selected-address-preview').hide();
            $('#use-selected-address-btn').hide();
            this.customerAddresses = null;
        },

        /**
         * Handle when quote requires address selection
         * @param {Object} response
         * @param {Function} onSuccess
         * @param {Function} onError
         */
        handleAddressSelectionRequired: function (response, onSuccess, onError) {
            var self = this;
            var quoteData = response.quoteData;

            console.group('📍 Address Selection Required');
            console.log('Quote Data:', quoteData);
            console.log('Customer Addresses:', quoteData.customerAddresses);
            console.groupEnd();

            if (quoteData.customerAddresses && quoteData.customerAddresses.addresses && quoteData.customerAddresses.addresses.length > 0) {
                // Show address selection UI
                this.showAddressSelection(quoteData.customerAddresses.addresses);

                // Store callbacks and quote data for later use
                this.pendingQuoteRequest = {
                    quoteData: quoteData,
                    onSuccess: onSuccess,
                    onError: onError
                };

                // Call a special callback to stop loading but show address selection message
                this.stopLoadingWithAddressSelection();

            } else {
                onError($t('No customer addresses found for this quote'));
            }
        },

        /**
         * Stop loading and show address selection message
         */
        stopLoadingWithAddressSelection: function () {
            // Hide loading overlay manually
            $('#loading-overlay').hide();
            $('#get-rates-btn').prop('disabled', false).find('span').text($t('Run Shipping Quote'));

            // Show address selection message
            this.showMessage($t('Please select a shipping address for this quote'), 'notice');
        },

        /**
         * Submit request with selected address
         */
        submitWithSelectedAddress: function () {
            var selectedAddressId = $('#selected_address_id').val();

            if (!selectedAddressId) {
                this.showMessage($t('Please select a shipping address'), 'error');
                return;
            }

            if (!this.pendingQuoteRequest) {
                this.showMessage($t('No pending quote request'), 'error');
                return;
            }

            // Prepare request data with selected address
            var requestData = this.prepareRequestData({
                request_type: 'quote',
                quote_id: this.pendingQuoteRequest.quoteData.quoteId,
                selected_address_id: selectedAddressId,
                transaction_id: $('#transaction_id').val() || '',
                residential: $('#residential').is(':checked'),
                lift_gate_required: $('#lift_gate_required').is(':checked'),
                delivery_notification: $('#delivery_notification').is(':checked')
            });

            var self = this;

            // Show loading
            this.showMessage($t('Getting shipping rates with selected address...'), 'notice');

            // Make request with selected address
            $.ajax({
                url: this.options.getQuoteRatesUrl,
                type: 'POST',
                data: requestData,
                success: function (response) {
                    if (response.success) {
                        // Store callback before clearing
                        var successCallback = self.pendingQuoteRequest.onSuccess;

                        // Hide address selection
                        self.hideAddressSelection();
                        // Clear pending request
                        self.pendingQuoteRequest = null;

                        // Call success callback
                        successCallback(response.data);
                    } else {
                        self.pendingQuoteRequest.onError(response.message || $t('An error occurred'));
                    }
                },
                error: function (xhr, status, error) {
                    var errorMessage = $t('Failed to get shipping rates. Please try again.');
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    self.pendingQuoteRequest.onError(errorMessage);
                }
            });
        },

        /**
         * Load products based on search term
         * @param {string} searchTerm
         */
        loadProducts: function (searchTerm) {
            var self = this;
            searchTerm = searchTerm || '';

            if (!this.options.getProductsUrl) {
                console.warn('Product URL not configured');
                return;
            }

            $.ajax({
                url: this.options.getProductsUrl,
                type: 'GET',
                data: {
                    term: searchTerm,
                    action: 'search'
                },
                beforeSend: function () {
                    $('#product_name').prop('disabled', true);
                },
                success: function (response) {
                    if (response.success) {
                        self.populateProductDropdown(response.data);
                    } else {
                        self.showMessage($t('Error loading products: ') + (response.message || ''), 'error');
                    }
                },
                error: function () {
                    self.showMessage($t('Error loading products'), 'error');
                },
                complete: function () {
                    $('#product_name').prop('disabled', false);
                }
            });
        },

        /**
         * Populate product dropdown with search results
         * @param {Array} products
         */
        populateProductDropdown: function (products) {
            var $resultsDropdown = $('#product_name_results');
            $resultsDropdown.empty();

            if (products.length === 0) {
                $resultsDropdown.append('<option value="">' + $t('No products found') + '</option>');
                $resultsDropdown.show();
                return;
            }

            products.forEach(function (product) {
                $resultsDropdown.append(
                    '<option value="' + product.value + '">' + product.label + '</option>'
                );
            });

            // Show results if there are any
            if (products.length > 0) {
                $resultsDropdown.show();
            }
        },

        /**
         * Handle product selection change
         * @param {string} productId
         */
        handleProductChange: function (productId) {
            if (!productId) {
                $('#sku').html('<option value="">' + $t('-- Please Select --') + '</option>');
                $('#unit_weight').val('');
                return;
            }

            this.loadProductSkus(productId);
        },

        /**
         * Load SKUs for selected product
         * @param {string} productId
         */
        loadProductSkus: function (productId) {
            var self = this;

            if (!this.options.getProductsUrl) {
                console.warn('Product URL not configured');
                return;
            }

            $.ajax({
                url: this.options.getProductsUrl,
                type: 'GET',
                data: {
                    product_id: productId,
                    action: 'get_skus'
                },
                beforeSend: function () {
                    $('#sku').html('<option value="">' + $t('Loading...') + '</option>').prop('disabled', true);
                },
                success: function (response) {
                    if (response.success) {
                        self.populateSkuDropdown(response.data);
                    } else {
                        self.showMessage($t('Error loading SKUs: ') + (response.message || ''), 'error');
                    }
                },
                error: function () {
                    self.showMessage($t('Error loading SKUs'), 'error');
                },
                complete: function () {
                    $('#sku').prop('disabled', false);
                }
            });
        },

        /**
         * Populate SKU dropdown
         * @param {Array} skus
         */
        populateSkuDropdown: function (skus) {
            var $skuSelect = $('#sku');
            $skuSelect.empty();
            $skuSelect.append('<option value="">' + $t('-- Please Select --') + '</option>');

            skus.forEach(function (sku) {
                var option = $('<option></option>')
                    .attr('value', sku.value)
                    .attr('data-weight', sku.weight)
                    .attr('data-sku', sku.sku)
                    .text(sku.label);
                $skuSelect.append(option);
            });
        },

        /**
         * Handle SKU selection change
         * @param {jQuery} selectedOption
         */
        handleSkuChange: function (selectedOption) {
            var weight = selectedOption.data('weight');
            if (weight) {
                // Round up weight to nearest integer as per business rules
                var roundedWeight = Math.ceil(parseFloat(weight));
                $('#unit_weight').val(roundedWeight);
            } else {
                $('#unit_weight').val('');
            }
        },

        /**
         * Validate quantity input
         * @param {jQuery} $field
         * @returns {boolean}
         */
        validateQuantity: function ($field) {
            var quantity = $field.val();
            var isValid = true;
            var errorMessage = '';

            // Remove previous error styling
            $field.removeClass('mage-error');
            $field.next('.field-error').remove();

            if (quantity !== '') {
                if (!$.isNumeric(quantity) || !Number.isInteger(parseFloat(quantity))) {
                    isValid = false;
                    errorMessage = $t('Quantity must be a whole number');
                } else {
                    var numericQuantity = parseInt(quantity);
                    if (numericQuantity <= 0) {
                        isValid = false;
                        errorMessage = $t('Quantity must be greater than 0');
                    } else if (numericQuantity > 10000) {
                        isValid = false;
                        errorMessage = $t('Quantity seems too high. Please verify.');
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
         * Validate unit weight input
         * @param {jQuery} $field
         * @returns {boolean}
         */
        validateUnitWeight: function ($field) {
            var weight = $field.val();
            var isValid = true;
            var errorMessage = '';

            // Remove previous error styling
            $field.removeClass('mage-error');
            $field.next('.field-error').remove();

            if (weight !== '') {
                if (!$.isNumeric(weight)) {
                    isValid = false;
                    errorMessage = $t('Unit weight must be a number');
                } else {
                    var numericWeight = parseFloat(weight);
                    if (numericWeight <= 0) {
                        isValid = false;
                        errorMessage = $t('Unit weight must be greater than 0');
                    } else if (numericWeight > 1000) {
                        isValid = false;
                        errorMessage = $t('Unit weight seems too high. Please verify.');
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
         * Validate quote number
         * @param {jQuery} $field
         * @returns {boolean}
         */
        validateQuoteId: function ($field) {
            var quoteId = $field.val();
            var isValid = true;
            var errorMessage = '';

            // Remove previous error styling
            $field.removeClass('mage-error');
            $field.next('.field-error').remove();

            if (quoteId !== '' && (!$.isNumeric(quoteId) || parseInt(quoteId) <= 0)) {
                isValid = false;
                errorMessage = $t('Quote ID must be a positive number');
            }

            if (!isValid) {
                $field.addClass('mage-error');
                $field.after('<div class="field-error" style="color: red; font-size: 12px; margin-top: 2px;">' +
                    errorMessage + '</div>');
            }

            return isValid;
        },

        /**
         * Validate form based on request type
         * @param {Object} formData
         * @returns {boolean}
         */
        validateForm: function (formData) {
            var isValid = true;
            var errors = [];

            if (this.currentRequestType === 'product') {
                isValid = this.validateProductForm(formData, errors);
            } else if (this.currentRequestType === 'quote') {
                isValid = this.validateQuoteForm(formData, errors);
            }

            // Show validation errors
            if (!isValid && errors.length > 0) {
                this.showValidationErrors(errors);
            }

            return isValid;
        },

        /**
         * Validate product form
         * @param {Object} formData
         * @param {Array} errors
         * @returns {boolean}
         */
        validateProductForm: function (formData, errors) {
            var isValid = true;

            // Required fields
            var requiredFields = {
                'product_name': $t('Product is required'),
                'sku': $t('SKU is required'),
                'quantity': $t('Quantity is required'),
                'unit_weight': $t('Unit weight is required')
            };

            Object.keys(requiredFields).forEach(function (field) {
                if (!formData[field] || formData[field].trim() === '') {
                    isValid = false;
                    errors.push(requiredFields[field]);
                    $('#' + field).addClass('mage-error');
                } else {
                    $('#' + field).removeClass('mage-error');
                }
            });

            // Validate quantity
            if (formData.quantity && !this.validateQuantity($('#quantity'))) {
                isValid = false;
            }

            // Validate unit weight
            if (formData.unit_weight && !this.validateUnitWeight($('#unit_weight'))) {
                isValid = false;
            }

            return isValid;
        },

        /**
         * Validate quote form
         * @param {Object} formData
         * @param {Array} errors
         * @returns {boolean}
         */
        validateQuoteForm: function (formData, errors) {
            var isValid = true;

            if (!formData.transaction_id || formData.transaction_id.trim() === '') {
                isValid = false;
                errors.push($t('Quote ID is required'));
                $('#transaction_id').addClass('mage-error');
            } else {
                $('#transaction_id').removeClass('mage-error');
                if (!this.validateQuoteId($('#transaction_id'))) {
                    isValid = false;
                }
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
         * Prepare request data based on request type
         * @param {Object} formData
         * @returns {Object}
         */
        prepareRequestData: function (formData) {
            var requestData = {
                request_type: this.currentRequestType,
                ship_to_zipcode: formData.ship_to_zipcode || '',
                ship_to_street: formData.ship_to_street || '',
                ship_to_city: formData.ship_to_city || '',
                ship_to_state: formData.ship_to_state || '',
                residential: formData.residential ? '1' : '',
                lift_gate_required: formData.lift_gate_required ? '1' : '',
                delivery_notification: formData.delivery_notification ? '1' : '',
                transaction_id: formData.transaction_id || ''
            };

            if (this.currentRequestType === 'product') {
                requestData.product_name = formData.product_name;
                requestData.sku = formData.sku;
                requestData.quantity = parseInt(formData.quantity);
                requestData.unit_weight = Math.ceil(parseFloat(formData.unit_weight)); // Round up as per business rules
            } else if (this.currentRequestType === 'quote') {
                requestData.quote_id = formData.transaction_id;
                if (formData.selected_address_id) {
                    requestData.selected_address_id = formData.selected_address_id;
                }
            }

            return requestData;
        },

        /**
         * Submit request
         * @param {Object} formData
         * @param {Function} onSuccess
         * @param {Function} onError
         */
        submitRequest: function (formData, onSuccess, onError) {
            var self = this;

            // Validate form first
            if (!this.validateForm(formData)) {
                return;
            }

            // Prepare request data
            var requestData = this.prepareRequestData(formData);

            // Determine the correct URL based on request type
            var apiUrl = this.currentRequestType === 'quote' ?
                this.options.getQuoteRatesUrl :
                this.options.getAllRatesUrl;

            // Make API call using standard form data format
            console.log('=== QUOTE REQUEST ===');
            console.log('Request URL:', apiUrl);
            console.log('Request Type:', this.currentRequestType);
            console.log('Request Data:', requestData);
            
            $.ajax({
                url: apiUrl,
                type: 'POST',
                data: requestData,
                success: function (response) {
                    console.log('=== QUOTE RESPONSE ===');
                    console.log('Full Response:', response);
                    
                    if (response.success) {
                        if (response.data) {
                            console.log('Quote Data:', response.data.quoteInfo || 'No quoteInfo in response');
                            console.log('Shipping Rates:', response.data.data || response.data);
                        }
                        onSuccess(response.data);
                    } else if (response.requiresAddressSelection) {
                        console.log('Quote requires address selection:', response);
                        // Quote needs address selection
                        self.handleAddressSelectionRequired(response, onSuccess, onError);
                    } else {
                        // Log debug information even on errors for quote requests
                        if (response.debugInfo) {
                            console.group('🚨 Quote Error Debug Information');
                            console.log('Error Message:', response.message);
                            console.log('Original Quote Data:', response.debugInfo.originalQuoteData);
                            console.log('Validation Errors:', response.debugInfo.validationErrors);
                            console.log('Full Error Response:', response);
                            console.groupEnd();
                        }
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

            $('.shipping-rates-container').prepend(messageHtml);

            // Auto-hide after 5 seconds
            setTimeout(function () {
                $('.shipping-rates-message').fadeOut();
            }, 5000);
        }
    };
});
