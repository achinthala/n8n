<?php

namespace Dcw\RequestQuote\Block\Account\Quote\Item\Price;

use Amasty\RequestQuote\Block\Account\Quote\Item\Price\Renderer as AmastyRenderer;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;

class Renderer extends AmastyRenderer
{
    /**
     * Override the getOriginalPrice method
     *
     * @return float
     */
    private $calculator;
    private $context;
    private $priceCurrency;
    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Pricing\Adjustment\Calculator $calculator,
        Template\Context $context,
        array $data = []
    ) {
        $this->context = $context;
        $this->calculator = $calculator;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($priceCurrency, $calculator,$context, $data);
    }

    public function getOriginalPrice()
    {
        // Your custom logic for getting the original price
        return $this->calculator->getAmount(
            $this->getItem()->getCustomPrice() ?? $this->getItem()->getProduct()->getFinalPrice(),
            $this->getItem()->getProduct()
        )->getValue(['tax', 'weee_tax', 'weee']);
       
        //return $this->getItem()->getCustomPrice() ?? $this->getItem()->getProduct()->getFinalPrice();
    }
}
