<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Plugin\Cart;

use Magento\Checkout\Model\Cart;
use Amasty\RequestQuote\Model\Source\Status;
use Magento\Framework\App\RequestInterface;

/**
 * Plugin to allow editing approved quotes by setting status to PENDING in memory
 */
class AllowEditApprovedQuote
{
    /**
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly RequestInterface $request
    ) {
    }

    /**
     * Set quote status to PENDING if editing approved quote
     * This bypasses Amasty's validation that prevents editing approved quotes
     *
     * @param Cart $subject
     * @param \Magento\Quote\Model\Quote $result
     * @return \Magento\Quote\Model\Quote
     */
    public function afterGetQuote(Cart $subject, $result)
    {
        if (!$result || !$result->getId()) {
            return $result;
        }

        // Check if we're editing (edit_quote_id or relation_parent_id in request)
        $editQuoteId = $this->request->getParam('edit_quote_id') 
                    ?: $this->request->getParam('relation_parent_id')
                    ?: $this->request->getPostValue('edit_quote_id')
                    ?: $this->request->getPostValue('relation_parent_id');

        // Note: We don't change the status - the Qty observer handles allowing edits
        // of approved quotes by bypassing the validation without changing status

        return $result;
    }
}

