/**
 * Copyright © Dotcom Weavers. All rights reserved.
 * 
 * AJAX functionality for RevenueRanking product count updates
 */

define([
    'jquery',
    'mage/url'
], function ($, urlBuilder) {
    'use strict';

    return function (config, element) {
        var $element = $(element);
        var ajaxUrl = urlBuilder.build('revenueranking/ajax/productcount');
        var updateTimeout;
        
        // Configuration
        var options = $.extend({
            updateDelay: 300,
            retryAttempts: 3,
            retryDelay: 1000
        }, config || {});

        /**
         * Update product counts via AJAX
         */
        function updateProductCounts(retryCount) {
            retryCount = retryCount || 0;
            
            // Clear any existing timeout
            if (updateTimeout) {
                clearTimeout(updateTimeout);
            }
            
            // Get current URL parameters
            var urlParams = new URLSearchParams(window.location.search);
            var ajaxParams = {};
            
            // Copy all URL parameters to AJAX request
            for (var [key, value] of urlParams) {
                ajaxParams[key] = value;
            }
            
            // Add AJAX flag
            ajaxParams.isAjax = '1';
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: ajaxParams,
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    if (response.success) {
                        updateProductCountDisplays(response);
                        console.log('RevenueRanking: Product counts updated successfully', response);
                        
                        // Trigger custom event
                        $element.trigger('productCountUpdated', [response]);
                        $(document).trigger('revenueRanking:productCountUpdated', [response]);
                    } else {
                        console.error('RevenueRanking: Failed to update product counts:', response.error);
                        handleUpdateError(retryCount);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('RevenueRanking: AJAX error updating product counts:', error);
                    handleUpdateError(retryCount);
                }
            });
        }

        /**
         * Handle update errors with retry logic
         */
        function handleUpdateError(retryCount) {
            if (retryCount < options.retryAttempts) {
                console.log('RevenueRanking: Retrying product count update, attempt:', retryCount + 1);
                setTimeout(function() {
                    updateProductCounts(retryCount + 1);
                }, options.retryDelay);
            } else {
                console.error('RevenueRanking: Max retry attempts reached for product count update');
                $element.trigger('productCountUpdateFailed');
            }
        }

        /**
         * Update the display elements with new data
         */
        function updateProductCountDisplays(data) {
            // Update total products count
            $element.find('.total-products-count').text(data.total_products);
            $element.find('.filtered-products-count').text(data.filtered_products);
            
            // Update any elements with specific classes
            $('.product-count-total').text(data.total_products);
            $('.product-count-filtered').text(data.filtered_products);
            
            // Update the main product count info
            $element.find('.total-products').text(data.total_products);
            $element.find('.filtered-products').text(data.filtered_products);
            
            // Show/hide filtered products info based on whether filters are applied
            var $filteredInfo = $element.find('.filtered-products-info');
            if (data.filters_applied) {
                $filteredInfo.show();
            } else {
                $filteredInfo.hide();
            }
            
            // Add visual feedback for updates
            $element.addClass('updating');
            setTimeout(function() {
                $element.removeClass('updating');
            }, 500);
        }

        /**
         * Debounced update function
         */
        function debouncedUpdate() {
            if (updateTimeout) {
                clearTimeout(updateTimeout);
            }
            updateTimeout = setTimeout(updateProductCounts, options.updateDelay);
        }

        /**
         * Initialize event listeners
         */
        function initEventListeners() {
            var pageType = $element.data('page-type');
            
            // Listen for Mirasvit layered navigation AJAX updates
            if (pageType === 'category' || pageType === 'search') {
                // Listen for content updates from Mirasvit
                $(document).on('contentUpdated', function() {
                    console.log('RevenueRanking: Content updated - updating product counts');
                    debouncedUpdate();
                });
                
                // Listen for filter changes
                $(document).on('click', '.mst-nav__label-item a, .filter-option a', function(e) {
                    console.log('RevenueRanking: Filter clicked - updating product counts');
                    debouncedUpdate();
                });
                
                // Listen for price filter changes
                $(document).on('change', 'input[type="range"], .price-slider input', function() {
                    console.log('RevenueRanking: Price filter changed - updating product counts');
                    debouncedUpdate();
                });
                
                // Listen for checkbox/radio changes
                $(document).on('change', '.mst-nav__label-item input[type="checkbox"], .mst-nav__label-item input[type="radio"]', function() {
                    console.log('RevenueRanking: Filter input changed - updating product counts');
                    debouncedUpdate();
                });
                
                // Listen for any AJAX requests that might affect product counts
                $(document).ajaxComplete(function(event, xhr, settings) {
                    if (settings.url && (settings.url.includes('catalog') || settings.url.includes('search') || settings.url.includes('filter'))) {
                        console.log('RevenueRanking: AJAX request completed - updating product counts');
                        debouncedUpdate();
                    }
                });
                
                // Listen for URL changes (for single page applications)
                var currentUrl = window.location.href;
                setInterval(function() {
                    if (window.location.href !== currentUrl) {
                        currentUrl = window.location.href;
                        console.log('RevenueRanking: URL changed - updating product counts');
                        debouncedUpdate();
                    }
                }, 1000);
            }
        }

        /**
         * Initialize the component
         */
        function init() {
            console.log('RevenueRanking: Initializing AJAX product count updates');
            
            // Initialize event listeners
            initEventListeners();
            
            // Initial load - get current counts
            updateProductCounts();
            
            // Expose public methods
            $element.data('revenueRankingAjax', {
                update: updateProductCounts,
                debouncedUpdate: debouncedUpdate
            });
        }

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            $(document).ready(init);
        } else {
            init();
        }
    };
});
