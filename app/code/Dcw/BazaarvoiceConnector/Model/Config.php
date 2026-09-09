<?php

declare(strict_types=1);

namespace Dcw\BazaarvoiceConnector\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    /**
     * path to configuration
     */
    private const XML_REMOVE_SAMPLES = 'bazaarvoice/feeds/remove_samples';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    /**
     * Is Remove samples Enabled
     * @return bool
     */
    public function isRemoveSamplesEnabled(): bool
    {
        return  $this->scopeConfig->isSetFlag(self::XML_REMOVE_SAMPLES, ScopeInterface::SCOPE_STORE);
    }
}
