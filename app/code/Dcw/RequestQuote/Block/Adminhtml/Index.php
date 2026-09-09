<?php

declare(strict_types=1);

namespace Dcw\RequestQuote\Block\Adminhtml;

use Dcw\RequestQuote\Model\Source\AdminUsernames;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;

class Index extends Template
{
    /**
     * @var AdminUsernames
     */
    protected AdminUsernames $dcwModelAdminUser;

    /**
     * @var ResourceConnection
     */
    protected ResourceConnection $resource;

    /**
     * @var QuoteFactory
     */
    protected QuoteFactory $quoteFactory;

    /**
     * @var QuoteResource
     */
    protected QuoteResource $quoteResource;

    /**
     * @var ItemFactory
     */
    protected ItemFactory $itemFactory;

    /**
     * @param Context $context
     * @param AdminUsernames $dcwModelAdminUser
     * @param ResourceConnection $resource
     * @param QuoteFactory $quoteFactory
     * @param QuoteResource $quoteResource
     * @param ItemFactory $itemFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        AdminUsernames $dcwModelAdminUser,
        ResourceConnection $resource,
        QuoteFactory $quoteFactory,
        QuoteResource $quoteResource,
        ItemFactory $itemFactory,
        array $data = []
    ) {
        $this->dcwModelAdminUser = $dcwModelAdminUser;
        $this->resource = $resource;
        $this->quoteFactory = $quoteFactory;
        $this->quoteResource = $quoteResource;
        $this->itemFactory = $itemFactory;

        parent::__construct($context, $data);
    }

    public function getAdminUserNames()
    {
        return $this->dcwModelAdminUser->toOptionArray();
    }

    /**
     * Resolve reserved_order_id from the related cart linked via amasty_quote_id option.
     *
     * @param int|string $quoteId Amasty quote entity id
     * @return string|null
     */
    public function getReservedOrderId($quoteId)
    {
        $resolved = $this->getReservedOrderIds([(int) $quoteId]);

        return $resolved[(int) $quoteId] ?? null;
    }

    /**
     * Bulk-resolve reserved_order_id for amasty quote ids (single SQL join).
     *
     * @param int[] $quoteIds
     * @return array<int, string> amasty quote entity_id => reserved_order_id
     */
    public function getReservedOrderIds(array $quoteIds): array
    {
        $quoteIds = array_values(array_unique(array_filter(array_map('intval', $quoteIds))));
        if (!$quoteIds) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(
                ['qio' => $connection->getTableName('quote_item_option')],
                ['amasty_quote_id' => 'value']
            )
            ->joinInner(
                ['qi' => $connection->getTableName('quote_item')],
                'qi.item_id = qio.item_id',
                []
            )
            ->joinInner(
                ['q' => $connection->getTableName('quote')],
                'q.entity_id = qi.quote_id',
                ['reserved_order_id']
            )
            ->where('qio.code = ?', 'amasty_quote_id')
            ->where('qio.value IN (?)', $quoteIds)
            ->where('q.reserved_order_id IS NOT NULL')
            ->where('q.reserved_order_id != ?', '');

        $rows = $connection->fetchAll($select);
        $result = [];
        foreach ($rows as $row) {
            $amastyQuoteId = (int) $row['amasty_quote_id'];
            if (!isset($result[$amastyQuoteId]) && !empty($row['reserved_order_id'])) {
                $result[$amastyQuoteId] = (string) $row['reserved_order_id'];
            }
        }

        return $result;
    }

    public function getQuoteByItemId($itemId)
    {
        $item = $this->itemFactory->create()->load($itemId);

        if (!$item->getId()) {
            return null;
        }

        $quoteId = $item->getQuoteId();
        $quote = $this->quoteFactory->create();
        $this->quoteResource->load($quote, $quoteId);

        return $quote;
    }

    public function checkReservedOrderId($quote)
    {
        return $quote->getReservedOrderId();
    }
}
