<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Incstores\ViewShippingRates\Model\Source\WeightClass;
use Incstores\ViewShippingRates\Model\Source\RequestType;

class Index extends Template
{
    /**
     * @var WeightClass
     */
    private $weightClassSource;

    /**
     * @var RequestType
     */
    private $requestTypeSource;

    /**
     * @param Context $context
     * @param WeightClass $weightClassSource
     * @param RequestType $requestTypeSource
     * @param array $data
     */
    public function __construct(
        Context $context,
        WeightClass $weightClassSource,
        RequestType $requestTypeSource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->weightClassSource = $weightClassSource;
        $this->requestTypeSource = $requestTypeSource;
    }

    /**
     * Get weight class options
     *
     * @return array
     */
    public function getWeightClassOptions(): array
    {
        return $this->weightClassSource->toOptionArray();
    }

    /**
     * Get request type options
     *
     * @return array
     */
    public function getRequestTypeOptions(): array
    {
        return $this->requestTypeSource->toOptionArray();
    }

    /**
     * Get AJAX URL for getting products
     *
     * @return string
     */
    public function getGetProductsUrl(): string
    {
        return $this->getUrl('viewshippingrates/ajax/getProducts');
    }

    /**
     * Get AJAX URL for getting quote
     *
     * @return string
     */
    public function getGetQuoteUrl(): string
    {
        return $this->getUrl('viewshippingrates/ajax/getQuote');
    }

    /**
     * Get auto quote ID from URL parameter
     *
     * @return string|null
     */
    public function getAutoQuoteId(): ?string
    {
        return $this->getData('auto_quote_id');
    }

    /**
     * Get auto order ID from URL parameter
     *
     * @return string|null
     */
    public function getAutoOrderId(): ?string
    {
        return $this->getData('auto_order_id');
    }

    /**
     * Get initialization parameters for JavaScript
     *
     * @return array
     */
    public function getInitParams(): array
    {
        $params = [];
        
        if ($this->getAutoQuoteId()) {
            $params['autoQuoteId'] = $this->getAutoQuoteId();
        }
        
        if ($this->getAutoOrderId()) {
            $params['autoOrderId'] = $this->getAutoOrderId();
        }
        
        return $params;
    }
}