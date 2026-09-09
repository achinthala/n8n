<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider
{
    public const XML_PATH_ALLOW_QUOTE_EDIT = 'requestquote/general/allow_quote_edit';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Get allow quote edit configuration value
     *
     * @param int|null $storeId
     * @return int
     */
    public function getAllowQuoteEdit(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_ALLOW_QUOTE_EDIT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}

