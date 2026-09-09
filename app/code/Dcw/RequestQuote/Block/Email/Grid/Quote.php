<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Request a Quote Base for Magento 2
 */

namespace Dcw\RequestQuote\Block\Email\Grid;

use Amasty\RequestQuote\Api\Data\QuoteInterface;
use Amasty\RequestQuote\Model\Email\AdminNotification;
use Magento\Framework\View\Element\Template;
use Amasty\RequestQuote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Amasty\RequestQuote\Model\Source\Status;

class Quote extends \Amasty\RequestQuote\Block\Email\Grid\Quote
{
   /**
     * @var string
     */
    protected $_template = 'Dcw_RequestQuote::email/grid/quotes.phtml';

    /**
     * @var null|\Amasty\RequestQuote\Model\ResourceModel\Quote\Collection
     */
    private $quoteCollection = null;

    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var null|array
     */
    private $ids = null;

    public function __construct(
        QuoteCollectionFactory $quoteCollectionFactory,
        Template\Context $context,
        array $data = []
    ) {
        // Adjust this line to match the parent class's constructor
        parent::__construct($quoteCollectionFactory, $context, $data);
        
        $this->quoteCollectionFactory = $quoteCollectionFactory;
    }

    /**
     * @param array $ids
     */
    public function addIdFilter($ids)
    {
        $this->ids = $ids;
    }

    /**
     * @return \Amasty\RequestQuote\Model\ResourceModel\Quote\Collection|null
     */
    public function getQuoteCollection()
    {
        if ($this->quoteCollection === null) {
            $this->quoteCollection = $this->quoteCollectionFactory->create()
                ->addFieldToFilter(
                    'status',
                    [
                        'nin' => [
                            Status::CREATED,
                            Status::ADMIN_NEW
                        ]
                    ]
                )->addFieldToFilter(
                    QuoteInterface::ADMIN_NOTIFICATION_SEND,
                    AdminNotification::NOT_SENT
                );
            if ($this->ids !== null) {
                $this->quoteCollection->addFieldToFilter(
                    'quote_id',
                    ['in' => $this->ids]
                );
            }
        }

        return $this->quoteCollection;
    }

    /**
     * @param \Amasty\RequestQuote\Model\ResourceModel\Quote\Collection|null $quoteCollection
     */
    public function setQuoteCollection($quoteCollection)
    {
        $this->quoteCollection = $quoteCollection;
    }

    public function getCustomerEmail(\Amasty\RequestQuote\Model\Quote $quote): string
    {
        if ($quote->getCustomerIsGuest()) {
            return (string)($quote->getCustomerEmail() ?? '');
        }
        
        $customer = $quote->getCustomer();
        if ($customer === null) {
            return '';
        }
        
        return (string)($customer->getEmail() ?? '');
    }
}
