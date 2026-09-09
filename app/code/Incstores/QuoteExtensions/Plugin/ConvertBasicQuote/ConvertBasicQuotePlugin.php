<?php

declare(strict_types=1);

namespace Incstores\QuoteExtensions\Plugin\ConvertBasicQuote;

use Amasty\RequestQuote\Api\Data\QuoteInterface as BasicQuoteInterface;
use Amasty\RequestQuote\Api\Data\CustomerAccount\QuoteInterface;
use Amasty\RequestQuote\Model\Quote\CustomerAccount\ConvertBasicQuote;

/**
 * Plugin to enhance Amasty quotes with Magento quote data after API conversion
 */
class ConvertBasicQuotePlugin
{
    /**
     * Enhance quote with Magento quote data after conversion
     *
     * @param ConvertBasicQuote $subject
     * @param QuoteInterface $result
     * @param BasicQuoteInterface $basicQuote
     * @return QuoteInterface
     */
    public function afterExecute(
        ConvertBasicQuote $subject,
        QuoteInterface $result,
        BasicQuoteInterface $basicQuote
    ): QuoteInterface {

        $grandTotal = $basicQuote->getGrandTotal();
        $adminUserAssistance = $basicQuote->getData('admin_user_assistance');
        if (!$adminUserAssistance) {
            $adminUserAssistance = '';
        }

        $extensionAttributes = $result->getExtensionAttributes();

        if ($extensionAttributes) {
            if (method_exists($extensionAttributes, 'setGrandTotal')) {
                $extensionAttributes->setGrandTotal($grandTotal);
            }
            if (method_exists($extensionAttributes, 'setAdminUserAssistance')) {
                $extensionAttributes->setAdminUserAssistance($adminUserAssistance);
            }
            $result->setExtensionAttributes($extensionAttributes);
        }
        return $result;
    }
}
