<?php

/**
 * Bazaarvoice FTP Import Manual Import Block
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 * @author   Dcw Team
 */

namespace Dcw\BazaarvoiceFtpImport\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ManualImport extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Dcw_BazaarvoiceFtpImport::system/config/manual_import.phtml';

    /**
     * ManualImport constructor.
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
        $buttonHtml .= '<button type="button" id="manual-import-btn" class="action-default scalable action-primary">';
        $buttonHtml .= '<span>Execute Import</span>';
        $buttonHtml .= '</button>';
        $buttonHtml .= '</div>';

        $buttonHtml .= '<script type="text/javascript">
            require(["jquery"], function($) {
                $("#manual-import-btn").click(function() {
                    var button = $(this);
                    button.prop("disabled", true);
                    button.find("span").text("Importing...");

                    $.ajax({
                        url: "' . $this->getManualImportUrl() . '",
                        type: "POST",
                        data: {
                            form_key: $("input[name=\'form_key\']").val()
                        },
                        success: function(response) {
                            if (response.success) {
                                var message = "Import Completed!";
                                if (response.message) {
                                    message += " " + response.message;
                                }
                                if (response.processed_files !== undefined) {
                                    message += " Processed " + response.processed_files + " files.";
                                }
                                if (response.downloaded_files !== undefined) {
                                    message += " Downloaded " + response.downloaded_files + " files.";
                                }
                                if (response.extracted_files !== undefined) {
                                    message += " Extracted " + response.extracted_files + " files.";
                                }
                                button.find("span").text(message);
                                button.removeClass("action-primary").addClass("action-success");
                                
                                // Show success message in green
                                button.css({
                                    "background-color": "#5cb85c",
                                    "border-color": "#4cae4c",
                                    "color": "#fff"
                                });
                            } else {
                                button.find("span").text("Import Failed: " + response.message);
                                button.removeClass("action-primary").addClass("action-danger");
                                
                                // Show error message in red
                                button.css({
                                    "background-color": "#d9534f",
                                    "border-color": "#d43f3a",
                                    "color": "#fff"
                                });
                            }
                            setTimeout(function() {
                                button.find("span").text("Execute Import");
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
                                    errorMessage = "Import failed: " + error;
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
                                button.find("span").text("Execute Import");
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
     * Get manual import URL
     *
     * @return string
     */
    public function getManualImportUrl()
    {
        return $this->getUrl('bazaarvoice_ftp_import/import/execute');
    }
}
