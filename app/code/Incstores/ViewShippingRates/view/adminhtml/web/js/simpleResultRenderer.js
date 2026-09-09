/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Simple Result Renderer Module
 * Handles rendering and display optimization for simple endpoint shipping rate results
 */

define([
    'jquery',
    'mage/template',
    'mage/translate'
], function ($, mageTemplate, $t) {
    'use strict';

    return {
        options: {},
        currentData: null,
        sortOrder: 'cost-asc', // default sort
        showInvalidRates: true,

        /**
         * Initialize the simple result renderer
         * @param {Object} config
         */
        initialize: function (config) {
            this.options = config;
            this.bindEvents();
        },

        /**
         * Bind events for result interaction
         */
        bindEvents: function () {
            var self = this;

            // Sort controls
            $(document).on('change', '#simple-sort-select', function () {
                self.sortOrder = $(this).val();
                if (self.currentData) {
                    self.renderResults(self.currentData);
                }
            });


            // Rate selection
            $(document).on('click', '.simple-rate-row', function () {
                self.highlightSelectedRate($(this));
            });
        },

        /**
         * Render simple endpoint results
         * @param {Object} data
         */
        renderResults: function (data) {
            this.currentData = data;

            // Display API status
            this.renderApiStatus(data);

            // Display results table
            if (data.lineItemShipments && data.lineItemShipments.length > 0) {
                this.renderResultsTable(data.lineItemShipments);
                this.renderSummaryStats(data.lineItemShipments);
            } else {
                this.renderNoResults();
            }

            // Show results section
            $('.results-section').show();
            this.scrollToResults();
        },

        /**
         * Render API status information
         * @param {Object} data
         */
        renderApiStatus: function (data) {
            var statusClass = data.isSuccessful ? 'success' : 'error';
            var statusText = data.isSuccessful ? $t('Success') : $t('Error');
            var statusCode = data.statusCode || 'N/A';

            var statusHtml = '<div class="api-status ' + statusClass + '">' +
                '<h3>' + $t('API Response Status') + '</h3>' +
                '<p><strong>' + $t('Status:') + '</strong> ' + statusText + '</p>' +
                '<p><strong>' + $t('Status Code:') + '</strong> ' + statusCode + '</p>' +
                '<p><strong>' + $t('Message:') + '</strong> ' + (data.message || $t('Request completed successfully')) + '</p>' +
                '</div>';

            $('#api-status').html(statusHtml);
        },

        /**
         * Render results table optimized for simple endpoint
         * @param {Array} shipments
         */
        renderResultsTable: function (shipments) {
            // Filter and sort shipments
            var filteredShipments = this.filterShipments(shipments);
            var sortedShipments = this.sortShipments(filteredShipments);

            // Build table HTML
            var tableHtml = this.buildTableHeader() + this.buildTableBody(sortedShipments) + '</tbody></table></div>';

            // Add controls
            var controlsHtml = this.buildResultControls(shipments, filteredShipments);

            $('#results-table-container').html(controlsHtml + tableHtml);
        },

        /**
         * Build result controls (sort, filter, etc.)
         * @param {Array} allShipments
         * @param {Array} filteredShipments
         * @returns {string}
         */
        buildResultControls: function (allShipments, filteredShipments) {
            var validCount = allShipments.filter(function (s) { return s.isValid !== false; }).length;
            var invalidCount = allShipments.length - validCount;

            var controlsHtml = '<div class="simple-results-controls" style="margin-bottom: 15px;">' +
                '<div class="control-row">' +
                '<div class="sort-control">' +
                '<label for="simple-sort-select">' + $t('Sort by:') + '</label> ' +
                '<select id="simple-sort-select">' +
                '<option value="cost-asc"' + (this.sortOrder === 'cost-asc' ? ' selected' : '') + '>' + $t('Cost (Low to High)') + '</option>' +
                '<option value="cost-desc"' + (this.sortOrder === 'cost-desc' ? ' selected' : '') + '>' + $t('Cost (High to Low)') + '</option>' +
                '<option value="carrier"' + (this.sortOrder === 'carrier' ? ' selected' : '') + '>' + $t('Carrier Name') + '</option>' +
                '<option value="charge-asc"' + (this.sortOrder === 'charge-asc' ? ' selected' : '') + '>' + $t('Total Charge (Low to High)') + '</option>' +
                '</select>' +
                '</div>';


            controlsHtml += '</div></div>';

            return controlsHtml;
        },

        /**
         * Build table header
         * @returns {string}
         */
        buildTableHeader: function () {
            return '<div class="admin__data-grid-wrap">' +
                '<table class="admin__data-grid-table simple-rates-table">' +
                '<thead>' +
                '<tr>' +
                '<th class="col-carrier">' + $t('Carrier') + '</th>' +
                '<th class="col-cost">' + $t('Cost') + '</th>' +
                '<th class="col-charge">' + $t('Charge') + '</th>' +
                '<th class="col-message">' + $t('Details') + '</th>' +
                '</tr>' +
                '</thead>' +
                '<tbody>';
        },

        /**
         * Build table body
         * @param {Array} shipments
         * @returns {string}
         */
        buildTableBody: function (shipments) {
            var self = this;
            var tableBody = '';
            var bestCost = this.getBestCost(shipments);

            shipments.forEach(function (shipment, index) {
                var rowClass = 'simple-rate-row';
                if (shipment.isValid === false) {
                    rowClass += ' invalid-rate';
                }
                if (shipment.cost === bestCost && shipment.isValid !== false) {
                    rowClass += ' best-rate';
                }

                tableBody += '<tr class="' + rowClass + '" data-index="' + index + '">' +
                    self.buildCarrierCell(shipment) +
                    self.buildCostCell(shipment) +
                    self.buildChargeCell(shipment) +
                    self.buildMessageCell(shipment) +
                    '</tr>';
            });

            return tableBody;
        },

        /**
         * Build carrier cell
         * @param {Object} shipment
         * @returns {string}
         */
        buildCarrierCell: function (shipment) {
            var carrierName = shipment.carrierName || 'Unknown';
            var actualCarrierName = shipment.actualCarrierName || carrierName;

            var cellContent = '<strong>' + carrierName + '</strong>';
            if (actualCarrierName !== carrierName) {
                cellContent += '<br><small>(' + actualCarrierName + ')</small>';
            }

            return '<td class="col-carrier">' + cellContent + '</td>';
        },

        /**
         * Build cost cell
         * @param {Object} shipment
         * @returns {string}
         */
        buildCostCell: function (shipment) {
            var cost = parseFloat(shipment.cost || 0);
            var formattedCost = cost > 0 ? '$' + cost.toFixed(2) : '-';

            return '<td class="col-cost"><span class="cost-value">' + formattedCost + '</span></td>';
        },

        /**
         * Build charge cell
         * @param {Object} shipment
         * @returns {string}
         */
        buildChargeCell: function (shipment) {
            var charge = parseFloat(shipment.charge || 0);
            var formattedCharge = charge > 0 ? '$' + charge.toFixed(2) : '-';

            return '<td class="col-charge"><span class="charge-value">' + formattedCharge + '</span></td>';
        },

        /**
         * Build savings cell
         * @param {Object} shipment
         * @param {number} bestCost
         * @returns {string}
         */
        buildSavingsCell: function (shipment, bestCost) {
            var cost = parseFloat(shipment.cost || 0);
            var savings = '';

            if (cost > 0 && bestCost > 0 && cost > bestCost) {
                var savingsAmount = cost - bestCost;
                savings = '$' + savingsAmount.toFixed(2);
            } else if (cost === bestCost && cost > 0) {
                savings = '<span class="best-rate-label">' + $t('Best Rate') + '</span>';
            }

            return '<td class="col-savings">' + savings + '</td>';
        },

        /**
         * Build location cell
         * @param {Object} shipment
         * @returns {string}
         */
        buildLocationCell: function (shipment) {
            var location = shipment.fulfilmentLocation || {};
            var content = '';

            if (location.name) {
                content += location.name;
            }
            if (location.zipCode) {
                content += content ? '<br>' + location.zipCode : location.zipCode;
            }
            if (!content) {
                content = '-';
            }

            return '<td class="col-location">' + content + '</td>';
        },

        /**
         * Build package info cell
         * @param {Object} shipment
         * @returns {string}
         */
        buildPackageInfoCell: function (shipment) {
            var packageInfo = shipment.package || {};
            var content = '';

            if (packageInfo.units && packageInfo.unitWeight) {
                var totalWeight = packageInfo.units * packageInfo.unitWeight;
                content = packageInfo.units + ' unit(s)<br>' + totalWeight + ' lbs total';
            } else if (packageInfo.unitWeight) {
                content = packageInfo.unitWeight + ' lbs';
            } else {
                content = '-';
            }

            return '<td class="col-weight">' + content + '</td>';
        },

        /**
         * Build message cell
         * @param {Object} shipment
         * @returns {string}
         */
        buildMessageCell: function (shipment) {
            var message = shipment.message || '';

            // Truncate long messages
            if (message.length > 50) {
                message = '<span title="' + message + '">' + message.substring(0, 50) + '...</span>';
            }

            return '<td class="col-message">' + message + '</td>';
        },

        /**
         * Build status cell
         * @param {Object} shipment
         * @returns {string}
         */
        buildStatusCell: function (shipment) {
            var status = '';
            var statusClass = '';

            if (shipment.isValid === false) {
                status = $t('Invalid');
                statusClass = 'status-invalid';
            } else {
                status = $t('Valid');
                statusClass = 'status-valid';
            }

            return '<td class="col-status"><span class="status-indicator ' + statusClass + '">' + status + '</span></td>';
        },

        /**
         * Filter shipments based on current settings
         * @param {Array} shipments
         * @returns {Array}
         */
        filterShipments: function (shipments) {
            if (this.showInvalidRates) {
                return shipments;
            }

            return shipments.filter(function (shipment) {
                return shipment.isValid !== false;
            });
        },

        /**
         * Sort shipments based on current sort order
         * @param {Array} shipments
         * @returns {Array}
         */
        sortShipments: function (shipments) {
            var sortedShipments = shipments.slice(); // Create copy

            switch (this.sortOrder) {
                case 'cost-asc':
                    sortedShipments.sort(function (a, b) {
                        return (parseFloat(a.cost) || 0) - (parseFloat(b.cost) || 0);
                    });
                    break;
                case 'cost-desc':
                    sortedShipments.sort(function (a, b) {
                        return (parseFloat(b.cost) || 0) - (parseFloat(a.cost) || 0);
                    });
                    break;
                case 'carrier':
                    sortedShipments.sort(function (a, b) {
                        return (a.carrierName || '').localeCompare(b.carrierName || '');
                    });
                    break;
                case 'charge-asc':
                    sortedShipments.sort(function (a, b) {
                        return (parseFloat(a.charge) || 0) - (parseFloat(b.charge) || 0);
                    });
                    break;
            }

            return sortedShipments;
        },

        /**
         * Get the best (lowest) cost from valid shipments
         * @param {Array} shipments
         * @returns {number}
         */
        getBestCost: function (shipments) {
            var validShipments = shipments.filter(function (s) {
                return s.isValid !== false && parseFloat(s.cost || 0) > 0;
            });

            if (validShipments.length === 0) {
                return 0;
            }

            return Math.min.apply(Math, validShipments.map(function (s) {
                return parseFloat(s.cost || 0);
            }));
        },

        /**
         * Render summary statistics
         * @param {Array} shipments
         */
        renderSummaryStats: function (shipments) {
            var validShipments = shipments.filter(function (s) { return s.isValid !== false; });
            var validCount = validShipments.length;
            var totalCount = shipments.length;
            var bestCost = this.getBestCost(validShipments);
            var avgCost = 0;

            if (validCount > 0) {
                var totalCost = validShipments.reduce(function (sum, s) {
                    return sum + (parseFloat(s.cost) || 0);
                }, 0);
                avgCost = totalCost / validCount;
            }

            // Get ship-to address from API request data
            var shipToHtml = this.buildShipToAddressHtml();
            
            var summaryHtml = '<div class="simple-results-summary">' +
                '<h4>' + $t('Rate Summary') + '</h4>' +
                '<div class="summary-stats">' +
                '<div class="stat-item">' +
                '<span class="stat-label">' + $t('Valid Rates:') + '</span> ' +
                '<span class="stat-value">' + validCount + ' of ' + totalCount + '</span>' +
                '</div>' +
                '<div class="stat-item">' +
                '<span class="stat-label">' + $t('Best Rate:') + '</span> ' +
                '<span class="stat-value">$' + bestCost.toFixed(2) + '</span>' +
                '</div>' +
                '<div class="stat-item">' +
                '<span class="stat-label">' + $t('Average Rate:') + '</span> ' +
                '<span class="stat-value">$' + avgCost.toFixed(2) + '</span>' +
                '</div>' +
                '</div>' +
                shipToHtml +
                '</div>';

            $('#results-summary').html(summaryHtml);
        },

        /**
         * Build ship-to address HTML for rate summary
         * @returns {string}
         */
        buildShipToAddressHtml: function () {
            if (!this.currentData || !this.currentData.debugInfo || !this.currentData.debugInfo.transformedApiRequest) {
                return '';
            }
            
            var apiRequest = this.currentData.debugInfo.transformedApiRequest;
            // Simple requests use 'deliverTo', quote/order requests use 'shipToAddress'
            var shipToAddress = apiRequest.shipToAddress || apiRequest.deliverTo;
            if (!shipToAddress) {
                return '';
            }
            
            return '<div class="ship-to-section">' +
                '<h5>' + $t('Ship To Address') + '</h5>' +
                '<div class="address-fields">' +
                '<div class="address-field">' +
                '<span class="field-label">' + $t('Street:') + '</span> ' +
                '<span class="field-value">' + (shipToAddress.street || '') + '</span>' +
                '</div>' +
                '<div class="address-field">' +
                '<span class="field-label">' + $t('City:') + '</span> ' +
                '<span class="field-value">' + (shipToAddress.city || '') + '</span>' +
                '</div>' +
                '<div class="address-field">' +
                '<span class="field-label">' + $t('State:') + '</span> ' +
                '<span class="field-value">' + (shipToAddress.state || '') + '</span>' +
                '</div>' +
                '<div class="address-field">' +
                '<span class="field-label">' + $t('Zip Code:') + '</span> ' +
                '<span class="field-value">' + (shipToAddress.zipCode || '') + '</span>' +
                '</div>' +
                '<div class="address-field">' +
                '<span class="field-label">' + $t('Country:') + '</span> ' +
                '<span class="field-value">' + (shipToAddress.country || '') + '</span>' +
                '</div>' +
                '</div>' +
                '</div>';
        },

        /**
         * Render no results message
         */
        renderNoResults: function () {
            var noResultsHtml = '<div class="no-results">' +
                '<h3>' + $t('No Shipping Rates Found') + '</h3>' +
                '<p>' + $t('No valid shipping rates were returned for this request. Please check your inputs and try again.') + '</p>' +
                '</div>';

            $('#results-table-container').html(noResultsHtml);
            $('#results-summary').empty();
        },

        /**
         * Highlight selected rate
         * @param {jQuery} $row
         */
        highlightSelectedRate: function ($row) {
            $('.simple-rate-row').removeClass('selected');
            $row.addClass('selected');
        },

        /**
         * Scroll to results section
         */
        scrollToResults: function () {
            $('html, body').animate({
                scrollTop: $('.results-section').offset().top - 20
            }, 500);
        }
    };
});
