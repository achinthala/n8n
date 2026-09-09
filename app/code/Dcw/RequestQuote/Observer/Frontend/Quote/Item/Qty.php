<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Observer\Frontend\Quote\Item;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Amasty\RequestQuote\Model\Source\Status;
use Magento\Framework\App\RequestInterface;

/**
 * Override Amasty's observer to allow editing approved quotes
 */
class Qty implements ObserverInterface
{
    /**
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly RequestInterface $request
    ) {
    }

    /**
     * Allow editing approved quotes by bypassing the validation
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        // $quoteItem = $observer->getEvent()->getItem();
        // if ($quoteItem->getOptionByCode('amasty_quote_price') &&
        //     $quoteItem->getOrigData('qty') != $quoteItem->getData('qty')
        // ) {
        //     $quoteItem->setHasError(true);
        //     $quoteItem->setMessage(__('It is not possible to edit items quantity of the approved Quote.'));
        // }

        return $this;
    }
}

