/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Shipping Rates Coordinator Module
 * Main coordinator that manages request type selection and delegates to appropriate requesters/renderers
 */

define([
    'jquery',
    'mage/translate',
    'Incstores_ViewShippingRates/js/simpleRequester',
    'Incstores_ViewShippingRates/js/allRequester',
    'Incstores_ViewShippingRates/js/orderRequester',
    'Incstores_ViewShippingRates/js/simpleResultRenderer',
    'Incstores_ViewShippingRates/js/allResultRenderer',
    'domReady!'
], function ($, $t, simpleRequester, allRequester, orderRequester, simpleResultRenderer, allResultRenderer) {
    'use strict';

    var shippingRatesCoordinator = {
        options: {},
        currentRequestType: null,
        currentRequester: null,
        currentRenderer: null,

        /**
         * Initialize the coordinator
         * @param {Object} config
         */
        initialize: function (config) {
            this.options = config;

            // Initialize all modules
            this.initializeModules();

            // Bind coordinator events
            this.bindEvents();

            // Initialize form state
            this.initializeFormState();

            // Handle auto-initialization from URL parameters
            this.handleAutoInitialization();
        },

        /**
         * Initialize all sub-modules
         */
        initializeModules: function () {
            // Initialize requesters
            simpleRequester.initialize({
                getSimpleRatesUrl: this.options.getSimpleRatesUrl
            });

            allRequester.initialize({
                getAllRatesUrl: this.options.getAllRatesUrl,
                getQuoteRatesUrl: this.options.getQuoteRatesUrl,
                getCustomerAddressesUrl: this.options.getCustomerAddressesUrl,
                getProductsUrl: this.options.getProductsUrl
            });

            orderRequester.initialize({
                getOrderUrl: this.options.getOrderUrl,
                getOrderRatesUrl: this.options.getOrderRatesUrl
            });

            // Initialize renderers
            simpleResultRenderer.initialize({});
            allResultRenderer.initialize({});
        },

        /**
         * Bind coordinator-level events
         */
        bindEvents: function () {
            var self = this;

            // Request type change handler
            $('#request_type').on('change', function () {
                self.handleRequestTypeChange($(this).val());
            });

            // Form submission handler
            $('#get-rates-btn').on('click', function (e) {
                e.preventDefault();
                self.submitForm();
            });
        },

        /**
         * Initialize form state
         */
        initializeFormState: function () {

            this.setupDefaultState();


            // Initialize placeholder tooltips for common fields
            this.initializePlaceholderTooltips();
        },

        /**
         * Handle request type change
         * @param {string} requestType
         */
        handleRequestTypeChange: function (requestType) {
            this.currentRequestType = requestType;

            // Reset form state
            this.resetFormState();

            // Show/hide appropriate sections and set up requester/renderer
            switch (requestType) {
                case 'simple':
                    this.setupSimpleRequest();
                    break;
                case 'product':
                    this.setupProductRequest();
                    break;
                case 'quote':
                    this.setupQuoteRequest();
                    break;
                case 'order':
                    this.setupOrderRequest();
                    break;
                default:
                    this.setupDefaultState();
            }
        },

        /**
         * Setup for simple requests
         */
        setupSimpleRequest: function () {
            // Show simple request elements
            $('#transaction_id, #shippingAddresses, #shipToAddress, #shipFromAddress, #delivery-options, #simple-request-parameters').show();
            // Hide other request elements
            $('#product-request-parameters, #quote-request-parameters, #order-request-parameters').hide();

            // Set up requester and renderer
            this.currentRequester = simpleRequester;
            this.currentRenderer = simpleResultRenderer;

            // Initialize simple request form
            simpleRequester.initializeFormState();
        },

        /**
         * Setup for product requests
         */
        setupProductRequest: function () {
            // Show product request elements
            $('#transaction_id, #product-request-parameters, #shippingAddresses, #shipToAddress, #delivery-options').show();
            // Hide other request elements
            $('#shipFromAddress, #simple-request-parameters, #quote-request-parameters, #order-request-parameters').hide();

            // Set up requester and renderer
            this.currentRequester = allRequester;
            this.currentRenderer = allResultRenderer;

            // Initialize product request form
            allRequester.initializeFormState('product');
        },

        /**
         * Setup for quote requests
         */
        setupQuoteRequest: function () {
            // Show quote-specific elements
            $('#transaction_id, #quote-request-parameters, #delivery-options').show();
            // Hide other parameter sections
            $('#simple-request-parameters, #product-request-parameters, #order-request-parameters, #shippingAddresses').hide();

            // Set up requester and renderer
            this.currentRequester = allRequester;
            this.currentRenderer = allResultRenderer;

            // Initialize quote request form
            allRequester.initializeFormState('quote');
        },

        /**
         * Setup for order requests
         */
        setupOrderRequest: function () {
            // Show order-specific elements
            $('#transaction_id, #delivery-options').show();
            // Hide other parameter sections
            $('#simple-request-parameters, #product-request-parameters, #quote-request-parameters, #shippingAddresses').hide();

            // Set up requester and renderer
            this.currentRequester = orderRequester;
            this.currentRenderer = allResultRenderer;

            // Initialize order request form
            orderRequester.initializeFormState();
        },

        /**
         * Setup default state (no request type selected)
         */
        setupDefaultState: function () {

            // Hide all conditional sections initially
            $('#simple-request-parameters, #product-request-parameters, #quote-request-parameters, #transaction_id, .results-section').hide();
            // Set default delivery options as checked
            $('#residential, #lift_gate_required, #delivery_notification').prop('checked', true);

            this.currentRequester = null;
            this.currentRenderer = null;
        },

        /**
         * Reset form state
         */
        resetFormState: function () {
            // Clear any validation errors
            $('.mage-error').removeClass('mage-error');
            $('.field-error').remove();

            // Hide results
            $('.results-section').hide();

            // Clear messages
            $('.shipping-rates-message').remove();
        },

        /**
         * Initialize placeholder tooltips for common form controls
         */
        initializePlaceholderTooltips: function () {
            var self = this;

            // Add tooltip functionality to common fields
            $('#ship_to_zipcode, #ship_to_street, #ship_to_city, #ship_to_state').each(function () {
                var $field = $(this);
                var placeholderText = $field.attr('placeholder');

                if (placeholderText) {
                    $field.on('mouseenter', function () {
                        if ($field.val().trim() !== '') {
                            self.showTooltip($field, placeholderText);
                        }
                    }).on('mouseleave', function () {
                        self.hideTooltip($field);
                    });
                }
            });
        },

        /**
         * Submit the form using the current requester
         */
        submitForm: function () {
            var self = this;

            if (!this.currentRequester) {
                this.showMessage($t('Please select a request type'), 'error');
                return;
            }

            // Get form data
            var formData = this.getFormData();

            // Show loading state
            this.showLoading(true);

            // Submit using current requester
            this.currentRequester.submitRequest(formData,
                function (data) {
                    // Success callback
                    self.showLoading(false);
                    if (self.currentRenderer) {
                        self.currentRenderer.renderResults(data);
                    } else {
                        self.showMessage($t('Results received but no renderer available'), 'notice');
                    }
                },
                function (errorMessage) {
                    // Error callback
                    self.showLoading(false);
                    self.showMessage(errorMessage, 'error');
                }
            );
        },

        /**
         * Get form data
         * @returns {Object}
         */
        getFormData: function () {
            var formData = {};

            // Serialize form fields
            $('#shipping-rates-form').serializeArray().forEach(function (field) {
                if (field.name.endsWith('[]')) {
                    var name = field.name.slice(0, -2);
                    if (!formData[name]) {
                        formData[name] = [];
                    }
                    formData[name].push(field.value);
                } else {
                    formData[field.name] = field.value;
                }
            });

            // Handle checkboxes explicitly
            formData.request_type = $('#request_type').val();
            formData.residential = $('#residential').is(':checked');
            formData.lift_gate_required = $('#lift_gate_required').is(':checked');
            formData.delivery_notification = $('#delivery_notification').is(':checked');

            // Ensure transaction_id is always included
            var transactionId = $('#transaction_id').val();
            if (transactionId) {
                formData.transaction_id = transactionId;
            }

            return formData;
        },

        /**
         * Show loading state
         * @param {boolean} show
         */
        showLoading: function (show) {
            if (show) {
                $('#loading-overlay').show();
                $('#get-rates-btn').prop('disabled', true).find('span').text($t('Loading...'));
            } else {
                $('#loading-overlay').hide();
                $('#get-rates-btn').prop('disabled', false).find('span').text($t('Run Shipping Quote'));
            }
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
        },

        /**
         * Show tooltip
         * @param {jQuery} $element
         * @param {string} text
         */
        showTooltip: function ($element, text) {
            // Remove any existing tooltip
            $('.field-tooltip').remove();

            // Create tooltip element
            var $tooltip = $('<div class="field-tooltip">' + text + '</div>');
            $tooltip.css({
                position: 'absolute',
                background: '#333',
                color: '#fff',
                padding: '5px 10px',
                borderRadius: '3px',
                fontSize: '12px',
                zIndex: 9999,
                whiteSpace: 'nowrap',
                boxShadow: '0 2px 5px rgba(0,0,0,0.2)'
            });

            // Position tooltip above the field
            var offset = $element.offset();
            $tooltip.css({
                top: offset.top - 30,
                left: offset.left
            });

            // Append to body
            $('body').append($tooltip);
        },

        /**
         * Hide tooltip
         * @param {jQuery} $element
         */
        hideTooltip: function ($element) {
            $('.field-tooltip').remove();
        },

        /**
         * Handle auto-initialization from URL parameters
         */
        handleAutoInitialization: function () {
            var self = this;
            
            // Check if initialization parameters were passed
            if (!this.options.initParams) {
                return;
            }
            
            var initParams = this.options.initParams;
            
            // Handle quote ID parameter
            if (initParams.autoQuoteId) {
                setTimeout(function () {
                    // Select quote request type
                    $('#request_type').val('quote').trigger('change');
                    
                    setTimeout(function () {
                        // Set the transaction ID
                        $('#transaction_id').val(initParams.autoQuoteId);
                        
                        // Submit the form automatically
                        self.submitForm();
                    }, 100);
                }, 100);
            }
            
            // Handle order ID parameter  
            if (initParams.autoOrderId) {
                setTimeout(function () {
                    // Select order request type
                    $('#request_type').val('order').trigger('change');
                    
                    setTimeout(function () {
                        // Set the transaction ID
                        $('#transaction_id').val(initParams.autoOrderId);
                        
                        // Submit the form automatically
                        self.submitForm();
                    }, 200);
                }, 100);
            }
        }
    };

    return shippingRatesCoordinator;
});
