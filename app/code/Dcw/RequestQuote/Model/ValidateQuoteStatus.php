<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Model;

use Amasty\RequestQuote\Api\Data\QuoteInterface;
use Dcw\RequestQuote\Model\Source\Edit\AllowQuoteEdit;
use Amasty\RequestQuote\Model\Source\Status;

class ValidateQuoteStatus
{
    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        private readonly ConfigProvider $configProvider
    ) {
    }

    /**
     * Validate if quote can be edited based on configuration
     *
     * @param QuoteInterface $quote
     * @return bool
     */
    public function validate(QuoteInterface $quote): bool
    {
        $allowEdit = $this->configProvider->getAllowQuoteEdit();
        $status = $quote->getStatus();

        if ($allowEdit === AllowQuoteEdit::NO) {
            return false;
        }

        switch ($allowEdit) {
            case AllowQuoteEdit::PENDING:
                return $status === Status::PENDING;
            case AllowQuoteEdit::APPROVED:
                return $status === Status::APPROVED;
            case AllowQuoteEdit::PENDING_AND_APPROVED:
                return $status === Status::PENDING || $status === Status::APPROVED;
            default:
                return false;
        }
    }
}

