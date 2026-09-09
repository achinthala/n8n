<?php

/**
 * Copy Credentials Button
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 * @author   Dcw Team
 */

namespace Dcw\BazaarvoiceFtpImport\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class CopyCredentialsButton extends Field
{
    /**
     * {@inheritdoc}
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $buttonHtml = '<div class="actions">';
        $buttonHtml .= '<button type="button" id="copy-credentials-btn" class="action-default scalable action-primary">';
        $buttonHtml .= '<span>Copy Bazaarvoice Credentials</span>';
        $buttonHtml .= '</button>';
        $buttonHtml .= '</div>';

        $buttonHtml .= '<script type="text/javascript">
            require(["jquery"], function($) {
                $("#copy-credentials-btn").click(function() {
                    var button = $(this);
                    button.prop("disabled", true);
                    button.find("span").text("Copying...");

                    $.ajax({
                        url: "' . $this->getAjaxUrl() . '",
                        type: "POST",
                        data: {
                            form_key: $("input[name=\'form_key\']").val()
                        },
                        success: function(response) {
                            if (response.success) {
                                button.find("span").text("Copied Successfully!");

                                // Update the disabled fields with the copied values
                                if (response.data) {
                                    if (response.data.host) {
                                        $("input[name*=\'bazaarvoice_ftp[ftp_settings][host]\']").val(response.data.host);
                                    }

                                    if (response.data.username) {
                                        $("input[name*=\'bazaarvoice_ftp[ftp_settings][username]\']").val(response.data.username);
                                    }

                                    if (response.data.password) {
                                        $("input[name*=\'bazaarvoice_ftp[ftp_settings][password]\']").val(response.data.password);
                                    }

                                }

                                // Auto-save the configuration after copying
                                setTimeout(function() {
                                    button.find("span").text("Saving Configuration...");

                                    // Find and submit the config form
                                    var configForm = $("#config-edit-form");
                                    if (configForm.length > 0) {
                                        // Set the form action to save
                                        configForm.attr("action", configForm.attr("action").replace("/edit/", "/save/"));
                                        configForm.submit();
                                    } else {
                                        // Fallback: reload page to show saved values
                                        window.location.reload();
                                    }

                                }, 1500);
                            } else {
                                button.find("span").text("Error: " + response.message);
                                setTimeout(function() {
                                    button.find("span").text("Copy Bazaarvoice Credentials");
                                    button.prop("disabled", false);
                                }, 3000);
                            }

                        },
                        error: function() {
                            button.find("span").text("Error occurred");
                            setTimeout(function() {
                                button.find("span").text("Copy Bazaarvoice Credentials");
                                button.prop("disabled", false);
                            }, 3000);
                        }

                    });
                });
            });
        </script>';

        return $buttonHtml;
    }

    /**
     * Get AJAX URL for copying credentials
     *
     * @return string
     */
    private function getAjaxUrl(): string
    {
        return $this->getUrl('bazaarvoice_ftp_import/import/copyCredentials');
    }
}
