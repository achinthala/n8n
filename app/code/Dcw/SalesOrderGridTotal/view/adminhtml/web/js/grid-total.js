/**
 * Grid Total - calculates total from visible grid rows
 */
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    console.log('[GridTotal] Module loaded');

    var totalAmount = 0;
    var totalCount = 0;
    var currencySymbol = '$';

    function formatCurrency(amount) {
        var n = parseFloat(amount);
        if (isNaN(n)) n = 0;
        return currencySymbol + n.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    function updateDisplay() {
        var formatted = formatCurrency(totalAmount);
        var html = $t('Total:') + ' <span class="total-amount">' + formatted + '</span> <span class="total-count">(' + totalCount + ' ' + $t('orders') + ')</span>';
        $('#grid-total-top').html(html);
        $('#grid-total-bottom').html(html);
        console.log('[GridTotal] Updated display: $' + totalAmount + ' (' + totalCount + ' orders)');
    }

    function addBottomTotal() {
        if ($('#grid-total-bottom').length > 0) return;
        var html = '<div id="grid-total-bottom" class="grid-total-container bottom">' +
            $t('Total:') + ' <span class="total-amount">' + formatCurrency(totalAmount) + '</span> <span class="total-count">(' + totalCount + ' ' + $t('orders') + ')</span>' +
            '</div>';
        var $wrap = $('.admin__data-grid-wrap').first();
        if ($wrap.length) {
            $wrap.after(html);
        } else {
            var $grid = $('[data-role="grid"]').first();
            if ($grid.length) {
                $grid.after(html);
            }
        }
    }

    function calculateFromDom() {
        totalAmount = 0;
        totalCount = 0;
        currencySymbol = '$';

        try {
            // Multiple strategies to find the table
            var $table = $('table.data-grid').first();
            if ($table.length === 0) {
                $table = $('.admin__data-grid-wrap table').first();
            }
            if ($table.length === 0) {
                $table = $('[data-role="grid"] table').first();
            }
            if ($table.length === 0) {
                console.log('[GridTotal] No table found');
                return;
            }

            console.log('[GridTotal] Found table with', $table.find('tbody tr').length, 'rows');

            // Find Grand Total column - try multiple strategies
            var grandTotalColIndex = -1;
            
            // Strategy 1: Find by data-index attribute
            $table.find('thead th').each(function (i) {
                var dataIndex = $(this).attr('data-index');
                if (dataIndex === 'base_grand_total' || dataIndex === 'grand_total') {
                    grandTotalColIndex = i;
                    console.log('[GridTotal] Found Grand Total column by data-index at index', i);
                    return false;
                }
            });

            // Strategy 2: Find by header text
            if (grandTotalColIndex < 0) {
                $table.find('thead th').each(function (i) {
                    var text = $(this).text().toLowerCase().trim();
                    if (text.indexOf('grand total') !== -1) {
                        grandTotalColIndex = i;
                        console.log('[GridTotal] Found Grand Total column by text at index', i, 'text:', text);
                        return false;
                    }
                });
            }

            // Strategy 3: Find the last numeric column (often Grand Total)
            if (grandTotalColIndex < 0) {
                var lastNumericIndex = -1;
                $table.find('thead th').each(function (i) {
                    var $th = $(this);
                    var text = $th.text().trim();
                    // Check if this column might contain prices
                    if (text && !text.match(/^(id|status|action|select|name|email|address)$/i)) {
                        lastNumericIndex = i;
                    }
                });
                if (lastNumericIndex >= 0) {
                    grandTotalColIndex = lastNumericIndex;
                    console.log('[GridTotal] Using last numeric column at index', grandTotalColIndex);
                }
            }

            // Get all visible data rows
            var $rows = $table.find('tbody tr').filter(function () {
                var $tr = $(this);
                return !$tr.hasClass('_head-row') && 
                       !$tr.hasClass('data-grid-tr-no-data') && 
                       !$tr.hasClass('data-grid-tr-no-data-row') &&
                       $tr.find('td').length > 0 &&
                       $tr.is(':visible');
            });

            console.log('[GridTotal] Found', $rows.length, 'data rows, Grand Total column index:', grandTotalColIndex);

            $rows.each(function (index, row) {
                var $row = $(row);
                var $cell = null;

                // Try to find Grand Total cell
                if (grandTotalColIndex >= 0) {
                    $cell = $row.find('td').eq(grandTotalColIndex);
                }
                if (!$cell || $cell.length === 0) {
                    $cell = $row.find('td[data-index="base_grand_total"]').first();
                }
                if (!$cell || $cell.length === 0) {
                    $cell = $row.find('td[data-index="grand_total"]').first();
                }
                if (!$cell || $cell.length === 0) {
                    // Try to find any cell that looks like a price (has $ and numbers)
                    $row.find('td').each(function () {
                        var text = $(this).text().trim();
                        if (text.match(/\$\d+/) || text.match(/\d+\.\d{2}/)) {
                            var num = parseFloat(text.replace(/[^\d.-]/g, ''));
                            if (!isNaN(num) && num > 0) {
                                $cell = $(this);
                                return false;
                            }
                        }
                    });
                }

                if ($cell && $cell.length > 0) {
                    var amountText = $cell.text().trim();
                    if (index === 0 && amountText) {
                        var match = amountText.match(/[^\d\s,.-]+/);
                        if (match) currencySymbol = match[0];
                    }
                    var amount = parseFloat(amountText.replace(/[^\d.-]/g, '')) || 0;
                    if (!isNaN(amount) && amount > 0) {
                        totalAmount += amount;
                        totalCount++;
                    }
                }
            });

            console.log('[GridTotal] Calculated: $' + totalAmount + ' from', totalCount, 'orders');
            updateDisplay();
        } catch (e) {
            console.error('[GridTotal] Error:', e);
        }
    }

    // Run immediately
    console.log('[GridTotal] Starting calculation...');
    calculateFromDom();
    addBottomTotal();

    // Run on document ready
    $(document).ready(function () {
        console.log('[GridTotal] Document ready, recalculating...');
        setTimeout(function () {
            calculateFromDom();
            addBottomTotal();
        }, 1000);
        setTimeout(function () {
            calculateFromDom();
            addBottomTotal();
        }, 3000);
    });

    // Run when grid updates via AJAX
    $(document).ajaxComplete(function (event, xhr, settings) {
        var url = (settings.url || '').toLowerCase();
        if (url.indexOf('sales/order') !== -1 || url.indexOf('mui/index/render') !== -1 || url.indexOf('grid') !== -1) {
            console.log('[GridTotal] Grid AJAX complete, recalculating...');
            setTimeout(function () {
                calculateFromDom();
                addBottomTotal();
            }, 1000);
        }
    });

    // Watch for DOM changes
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function () {
            setTimeout(calculateFromDom, 500);
        });
        setTimeout(function () {
            var target = document.querySelector('.admin__data-grid-wrap') || 
                        document.querySelector('[data-role="grid"]') ||
                        document.querySelector('table.data-grid');
            if (target) {
                console.log('[GridTotal] Setting up MutationObserver');
                observer.observe(target, { childList: true, subtree: true });
            }
        }, 2000);
    }

    // Periodic recalculation
    setInterval(function () {
        calculateFromDom();
        addBottomTotal();
    }, 5000);

    return {};
});
