<?php

/**
 * Manual GMC Convert Button Block
 *
 * @category Dcw
 * @package  Dcw_BazaarvoiceFtpImport
 * @author   Dcw Team
 */

namespace Dcw\BazaarvoiceFtpImport\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ManualGmcConvert extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Dcw_BazaarvoiceFtpImport::system/config/manual_gmc_convert.phtml';

    /**
     * ManualGmcConvert constructor.
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
        $buttonHtml .= '<button type="button" id="manual-gmc-convert-btn" class="action-default scalable action-primary">';
        $buttonHtml .= '<span>Convert to GMC</span>';
        $buttonHtml .= '</button>';
        $buttonHtml .= '</div>';

        $buttonHtml .= '<script type="text/javascript">
            require(["jquery"], function($) {
                $("#manual-gmc-convert-btn").click(function() {
                    var button = $(this);
                    button.prop("disabled", true);
                    button.find("span").text("Converting...");

                    $.ajax({
                        url: "' . $this->getAjaxUrl() . '",
                        type: "POST",
                        data: {
                            form_key: $("input[name=\'form_key\']").val()
                        },
                        success: function(response) {
                            if (response.success) {
                                var message = "Conversion Completed!";
                                if (response.files_processed !== undefined) {
                                    message += " Processed " + response.files_processed + " files.";
                                }
                                if (response.gmc_files_generated !== undefined) {
                                    message += " Generated " + response.gmc_files_generated + " GMC files.";
                                }
                                if (response.output_directory) {
                                    message += " Output: " + response.output_directory;
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
                                button.find("span").text("Conversion Failed: " + response.message);
                                button.removeClass("action-primary").addClass("action-danger");
                                
                                // Show error message in red
                                button.css({
                                    "background-color": "#d9534f",
                                    "border-color": "#d43f3a",
                                    "color": "#fff"
                                });
                            }
                            setTimeout(function() {
                                button.find("span").text("Convert to GMC");
                                button.prop("disabled", false);
                                button.removeClass("action-success action-danger").addClass("action-primary");
                                button.css({
                                    "background-color": "",
                                    "border-color": "",
                                    "color": ""
                                });
                            }, 5000);
                        },
                        error: function() {
                            button.find("span").text("Error occurred");
                            button.removeClass("action-primary").addClass("action-danger");
                            setTimeout(function() {
                                button.find("span").text("Convert to GMC");
                                button.prop("disabled", false);
                                button.removeClass("action-danger").addClass("action-primary");
                            }, 3000);
                        }
                    });
                });
            });
        </script>';

        return $buttonHtml;
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData([
            'id' => 'manual_gmc_convert',
            'label' => __('Convert to GMC'),
            'onclick' => 'manualGmcConvert()'
        ]);

        return $button->toHtml();
    }

    /**
     * Get AJAX URL for manual GMC convert
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('bazaarvoice_ftp_import/import/gmcConvert');
    }
}
