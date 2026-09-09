<?php

/**
 * Bazaarvoice FTP Import Test Connection Block
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 * @author   Dcw Team
 */

namespace Dcw\BazaarvoiceFtpImport\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class TestConnection extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Dcw_BazaarvoiceFtpImport::system/config/test_connection.phtml';

    /**
     * TestConnection constructor.
     *
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $buttonHtml = '<div class="actions">';
        $buttonHtml .= '<button type="button" id="test-connection-btn" class="action-default scalable action-primary">';
        $buttonHtml .= '<span>Test Connection</span>';
        $buttonHtml .= '</button>';
        $buttonHtml .= '</div>';

        $buttonHtml .= '<script type="text/javascript">
            require(["jquery"], function($) {
                $("#test-connection-btn").click(function() {
                    var button = $(this);
                    button.prop("disabled", true);
                    button.find("span").text("Testing...");

                    $.ajax({
                        url: "' . $this->getTestConnectionUrl() . '",
                        type: "POST",
                        data: {
                            form_key: $("input[name=\'form_key\']").val()
                        },
                        success: function(response) {
                            if (response.success) {
                                var message = "Connection Successful!";
                                if (response.files_found !== undefined) {
                                    message += " Found " + response.files_found + " files.";
                                }
                                if (response.message) {
                                    message += " " + response.message;
                                }
                                button.find("span").text(message);
                                button.removeClass("action-primary").addClass("action-success");
                                
                                // Show success message in a more prominent way
                                button.css({
                                    "background-color": "#5cb85c",
                                    "border-color": "#4cae4c",
                                    "color": "#fff"
                                });
                            } else {
                                button.find("span").text("Connection Failed: " + response.message);
                                button.removeClass("action-primary").addClass("action-danger");
                                
                                // Show error message in red
                                button.css({
                                    "background-color": "#d9534f",
                                    "border-color": "#d43f3a",
                                    "color": "#fff"
                                });
                            }
                            setTimeout(function() {
                                button.find("span").text("Test Connection");
                                button.prop("disabled", false);
                                button.removeClass("action-success action-danger").addClass("action-primary");
                                button.css({
                                    "background-color": "",
                                    "border-color": "",
                                    "color": ""
                                });
                            }, 5000);
                        },
                        error: function(xhr, status, error) {
                            var errorMessage = "Error occurred";
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                try {
                                    var response = JSON.parse(xhr.responseText);
                                    if (response.message) {
                                        errorMessage = response.message;
                                    }
                                } catch(e) {
                                    errorMessage = "Connection failed: " + error;
                                }
                            }
                            button.find("span").text("Error: " + errorMessage);
                            button.removeClass("action-primary").addClass("action-danger");
                            
                            // Show error message in red
                            button.css({
                                "background-color": "#d9534f",
                                "border-color": "#d43f3a",
                                "color": "#fff"
                            });
                            
                            setTimeout(function() {
                                button.find("span").text("Test Connection");
                                button.prop("disabled", false);
                                button.removeClass("action-danger").addClass("action-primary");
                                button.css({
                                    "background-color": "",
                                    "border-color": "",
                                    "color": ""
                                });
                            }, 5000);
                        }
                    });
                });
            });
        </script>';

        return $buttonHtml;
    }

    /**
     * Get test connection URL
     *
     * @return string
     */
    public function getTestConnectionUrl()
    {
        return $this->getUrl('bazaarvoice_ftp_import/import/testConnection');
    }
}
