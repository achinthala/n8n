define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, alert) {
    "use strict";

    var GTMGA4API = GTMGA4API || {};

    var optionsForm = $('#config-edit-form');

    var triggerJsonGenerateButton = $('#generate_gtmga4_api_json'),
        accountID = $('#weltpixel_ga4_api_account_id'),
        containerID = $('#weltpixel_ga4_api_container_id'),
        measurementID = $('#weltpixel_ga4_api_measurement_id'),
        enableConversionTracking = $('#weltpixel_ga4_adwords_conversion_tracking_enable'),
        enableAdwordsRemarketing = $('#weltpixel_ga4_adwords_remarketing_enable'),
        enableEnhancedConversion = $('#weltpixel_ga4_adwords_conversion_tracking_enable_enhanced_conversion'),
        sendEnhancedConversionToGA4 = $('#weltpixel_ga4_adwords_conversion_tracking_send_enhanced_conversion_to_ga4'),
        enableConversionCustomerAcquisition = $('#weltpixel_ga4_adwords_conversion_tracking_enable_new_customer_acquisition'),
        enableConversionCartData = $('#weltpixel_ga4_adwords_conversion_tracking_enable_cart_data'),
        conversionCartMerchantCenterId = $('#weltpixel_ga4_adwords_conversion_tracking_merchant_center_id'),
        conversionSeparateDatalayerEvent = $('#weltpixel_ga4_adwords_conversion_tracking_adwords_separate_datalayer_event'),
        conversionCartFeedCountry = $('#weltpixel_ga4_adwords_conversion_tracking_feed_country'),
        conversionCartFeedLanguage = $('#weltpixel_ga4_adwords_conversion_tracking_feed_language'),
        jsonExportPublicId = $("#weltpixel_ga4_json_export_public_id"),
        formKey = $('#api_form_key');

    var conversionId = $('#weltpixel_ga4_adwords_conversion_tracking_google_conversion_id'),
        conversionLabel = $('#weltpixel_ga4_adwords_conversion_tracking_google_conversion_label'),
        conversionCurrencyCode = $('#weltpixel_ga4_adwords_conversion_tracking_google_conversion_currency_code');

    var remarketingConversionCode = $('#weltpixel_ga4_adwords_remarketing_conversion_code'),
        remarketingConversionLabel = $('#weltpixel_ga4_adwords_remarketing_conversion_label');

    var serverSideEnableMeauserment = $('#weltpixel_ga4_serverside_measurement_enable'),
        serverSideTrackEventsMultiSelect = $('#weltpixel_ga4_serverside_measurement_events'),
        serverSideTrackEventsMultiSelectHidden = $('#weltpixel_ga4_serverside_measurement_events_hidden');

    var metaPixelEnableTracking = $('#weltpixel_ga4_meta_pixel_tracking_enable'),
        metaPixelTrackEventsMultiSelect = $('#weltpixel_ga4_meta_pixel_tracking_events'),
        metaPixelTrackEventsMultiSelectHidden = $('#weltpixel_ga4_meta_pixel_tracking_events_hidden');

    var excludeOrderByStatus = $('#weltpixel_ga4_general_exclude_order_by_status_flag'),
        excludeOrderByStatusMultiSelect = $('#weltpixel_ga4_general_exclude_order_by_statuses'),
        excludeOrderByStatusMultiSelectHidden = $('#weltpixel_ga4_general_exclude_order_by_statuses_hidden');


        GTMGA4API.initializeJsonGeneration = function(itemJsonGenerationUrl) {
        var that = this;
        $(triggerJsonGenerateButton).click(function() {
            $('.use-default .checkbox').each(function() {
                if ($(this).is(':checked')) {
                    $(this).trigger('click').addClass('forced-click');
                }
            });
            var validation = that._validateInputs();
            if (!validation.length) {
                validation = that._validateJsonExportInputs();
            }

            if (!validation.length && (parseInt(enableAdwordsRemarketing.val()) == 1)) {
                validation = that._validateRemarketingInputs();

                if (!validation.length && (parseInt(enableConversionCartData.val()) ==  1)) {
                    validation = that._enableConversionCartDataInputs();
                }
            }
            if (!validation.length && (parseInt(enableConversionTracking.val()) ==  1)) {
                validation = that._validateConversionTrackingInputs();
            }

            if (validation.length) {
                alert({content: validation.join('')});
            } else {
                $.ajax({
                    showLoader: true,
                    url: itemJsonGenerationUrl,
                    data: {
                        'form_key' : formKey.val(),
                        'account_id' : accountID.val().trim(),
                        'container_id' : containerID.val().trim(),
                        'measurement_id' : measurementID.val().trim(),
                        'conversion_enabled' : enableConversionTracking.val(),
                        'conversion_id' : conversionId.val().trim(),
                        'conversion_label' : conversionLabel.val().trim(),
                        'conversion_currency_code' : conversionCurrencyCode.val().trim(),
                        'remarketing_enabled' : enableAdwordsRemarketing.val(),
                        'remarketing_conversion_code' : remarketingConversionCode.val().trim(),
                        'remarketing_conversion_label' : remarketingConversionLabel.val().trim(),
                        'enable_enhanced_conversion' : enableEnhancedConversion.val(),
                        'send_enhanced_conversion_to_ga4' : sendEnhancedConversionToGA4.val(),
                        'enable_conversion_customer_acquisition' : enableConversionCustomerAcquisition.val(),
                        'enable_conversion_cart_data' : enableConversionCartData.val(),
                        'conversion_cart_merchant_center_id' : conversionCartMerchantCenterId.val().trim(),
                        'conversion_cart_feed_country' : conversionCartFeedCountry.val().trim(),
                        'conversion_cart_feed_language' : conversionCartFeedLanguage.val().trim(),
                        'conversion_separate_datalayer_event': conversionSeparateDatalayerEvent.val(),
                        'public_id' : jsonExportPublicId.val().trim(),
                        'form_data' : optionsForm.serialize()
                    },
                    type: "POST",
                    dataType: 'json'
                }).done(function (data) {
                    alert({content: data.msg.join('<br/>')});
                    if (data.jsonUrl) {
                        $('#download_gtmga4_json').show().css('display', 'inline-block§');
                    } else {
                        $('#download_gtmga4_json').hide();
                    }
                    $('.use-default .checkbox.forced-click').each(function() {
                        $(this).trigger('click').removeClass('forced-click');
                    });
                    $('.use-default .checkbox.forced-click').trigger('click').removeClass('forced-click');
                });
            }
        });
    };

    GTMGA4API._validateInputs = function () {
        var errors = [];
        if (accountID.val().trim() == '') {
            errors.push($.mage.__('Please specify the Account ID') + '<br/>');
        }
        if (containerID.val().trim() == '') {
            errors.push($.mage.__('Please specify the Container ID') + '<br/>');
        }
        if (measurementID.val().trim() == '') {
            errors.push($.mage.__('Please specify the Measurement ID') + '<br/>');
        }

        return errors;
    };

    GTMGA4API._validateConversionTrackingInputs = function () {
        var errors = [];
        if (accountID.val().trim() == '') {
            errors.push($.mage.__('Please specify the Account ID in GTM API Configuration section') + '<br/>');
        }
        if (containerID.val().trim() == '') {
            errors.push($.mage.__('Please specify the Container ID in GTM API Configuration section') + '<br/>');
        }
        if (conversionId.val().trim() == '') {
            errors.push($.mage.__('Please specify the Google Conversion Id') + '<br/>');
        }
        if (conversionLabel.val().trim() == '') {
            errors.push($.mage.__('Please specify the Google Conversion Label') + '<br/>');
        }
            if (conversionCurrencyCode.val().trim() == '') {
            errors.push($.mage.__('Please specify the Google Convesion Currency Code') + '<br/>');
        }

        return errors;
    };

    GTMGA4API._validateJsonExportInputs = function() {
        var errors = [];
        if (jsonExportPublicId.val().trim() == '') {
            errors.push($.mage.__('Please specify the Public Id') + '<br/>');
        }
        return errors;
    };

    GTMGA4API._validateRemarketingInputs = function () {
        var errors = [];
        if (accountID.val().trim() == '') {
            errors.push($.mage.__('Please specify the Account ID in GTM API Configuration section') + '<br/>');
        }
        if (containerID.val().trim() == '') {
            errors.push($.mage.__('Please specify the Container ID in GTM API Configuration section') + '<br/>');
        }
        if (remarketingConversionCode.val().trim() == '') {
            errors.push($.mage.__('Please specify the Google Ads Conversion ID') + '<br/>');
        }
        return errors;
    };


    GTMGA4API._enableConversionCartDataInputs = function () {
        var errors = [];
        if (conversionCartMerchantCenterId.val().trim() == '') {
            errors.push($.mage.__('Please specify the Merchant Center ID') + '<br/>');
        }
        if (conversionCartFeedCountry.val().trim() == '') {
            errors.push($.mage.__('Please specify the Feed Country') + '<br/>');
        }
        if (conversionCartFeedLanguage.val().trim() == '') {
            errors.push($.mage.__('Please specify the Feed Language') + '<br/>');
        }
        return errors;
    };


    GTMGA4API.MultiSelectDependenciesAdjustemnts = function() {
        serverSideEnableMeauserment.on('change', function() {
            serverSideTrackEventsMultiSelectHidden.val(serverSideTrackEventsMultiSelect.val());
        });
        metaPixelEnableTracking.on('change', function() {
            metaPixelTrackEventsMultiSelectHidden.val(metaPixelTrackEventsMultiSelect.val());
        });
        excludeOrderByStatus.on('change', function() {
            excludeOrderByStatusMultiSelectHidden.val(excludeOrderByStatusMultiSelect.val());
        });
    };

    return GTMGA4API;
});
