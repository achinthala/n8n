/**
 * Quote Grid Total - calculates total from visible grid rows (fallback).
 */
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

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
        var html = $t('Total:') + ' <span class="total-amount">' + formatted + '</span> <span class="total-count">(' + totalCount + ' ' + $t('quotes') + ')</span>';
        $('#quote-grid-total-top').html(html);
        $('#quote-grid-total-bottom').html(html);
    }

    function addBottomTotal() {
        if ($('#quote-grid-total-bottom').length > 0) return;
        var html = '<div id="quote-grid-total-bottom" class="grid-total-container bottom">' +
            $t('Total:') + ' <span class="total-amount">' + formatCurrency(totalAmount) + '</span> <span class="total-count">(' + totalCount + ' ' + $t('quotes') + ')</span>' +
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
            var $table = $('table.data-grid').first();
            if ($table.length === 0) {
                $table = $('.admin__data-grid-wrap table').first();
            }
            if ($table.length === 0) {
                $table = $('[data-role="grid"] table').first();
            }
            if ($table.length === 0) {
                return;
            }

            var grandTotalColIndex = -1;
            $table.find('thead th').each(function (i) {
                var dataIndex = $(this).attr('data-index');
                if (dataIndex === 'base_grand_total' || dataIndex === 'grand_total') {
                    grandTotalColIndex = i;
                    return false;
                }
            });

            if (grandTotalColIndex < 0) {
                $table.find('thead th').each(function (i) {
                    var text = $(this).text().toLowerCase().trim();
                    if (text.indexOf('grand total') !== -1) {
                        grandTotalColIndex = i;
                        return false;
                    }
                });
            }

            var $rows = $table.find('tbody tr').filter(function () {
                var $tr = $(this);
                return !$tr.hasClass('_head-row') &&
                    !$tr.hasClass('data-grid-tr-no-data') &&
                    !$tr.hasClass('data-grid-tr-no-data-row') &&
                    $tr.find('td').length > 0 &&
                    $tr.is(':visible');
            });

            $rows.each(function (index, row) {
                var $row = $(row);
                var $cell = null;

                if (grandTotalColIndex >= 0) {
                    $cell = $row.find('td').eq(grandTotalColIndex);
                }
                if (!$cell || $cell.length === 0) {
                    $cell = $row.find('td[data-index="base_grand_total"]').first();
                }
                if (!$cell || $cell.length === 0) {
                    $cell = $row.find('td[data-index="grand_total"]').first();
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

            updateDisplay();
        } catch (e) {}
    }

    calculateFromDom();
    addBottomTotal();

    $(document).ready(function () {
        setTimeout(function () {
            calculateFromDom();
            addBottomTotal();
        }, 1000);
        setTimeout(function () {
            calculateFromDom();
            addBottomTotal();
        }, 3000);
    });

    $(document).ajaxComplete(function (event, xhr, settings) {
        var url = (settings.url || '').toLowerCase();
        if (url.indexOf('amasty_quote') !== -1 || url.indexOf('mui/index/render') !== -1 || url.indexOf('grid') !== -1) {
            setTimeout(function () {
                calculateFromDom();
                addBottomTotal();
            }, 700);
        }
    });

    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function () {
            setTimeout(calculateFromDom, 500);
        });
        setTimeout(function () {
            var target = document.querySelector('.admin__data-grid-wrap') ||
                document.querySelector('[data-role="grid"]') ||
                document.querySelector('table.data-grid');
            if (target) {
                observer.observe(target, { childList: true, subtree: true });
            }
        }, 2000);
    }

    setInterval(function () {
        calculateFromDom();
        addBottomTotal();
    }, 5000);

    return {};
});

