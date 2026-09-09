<?php

declare(strict_types=1);

namespace Dcw\RequestQuote\Plugin\Quote;

use Amasty\RequestQuote\Model\Source\Status;
use Dcw\RequestQuote\Block\Adminhtml\Index as RequestQuoteBlock;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Enrich Amasty quote grid rows with reserved_order_id.
 *
 * The grid collection already selects from quote, so reserved_order_id is usually present.
 * Only resolve/persist when the value is missing. Avoids N+1 CartRepository loads that
 * previously timed out CSV export around page 8 (~1,600 rows).
 */
class CollectionPlugin
{
    public function __construct(
        private readonly RequestQuoteBlock $requestQuoteBlock,
        private readonly ResourceConnection $resourceConnection,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @param mixed $subject
     * @param \Magento\Framework\DataObject[] $items
     * @return \Magento\Framework\DataObject[]
     */
    public function afterGetItems($subject, $items)
    {
        if (!$items) {
            return $items;
        }

        $quoteIdsNeedingResolve = [];
        foreach ($items as $quote) {
            if (!$quote->getData('reserved_order_id')) {
                $quoteIdsNeedingResolve[] = (int) $quote->getId();
            }
        }

        $resolved = [];
        if ($quoteIdsNeedingResolve) {
            $resolved = $this->requestQuoteBlock->getReservedOrderIds($quoteIdsNeedingResolve);
        }

        $updates = [];
        $isExport = $this->isExportRequest();

        foreach ($items as $quote) {
            $quoteId = (int) $quote->getId();
            $reservedOrderId = $quote->getData('reserved_order_id');

            if (!$reservedOrderId && isset($resolved[$quoteId]) && $resolved[$quoteId]) {
                $reservedOrderId = $resolved[$quoteId];
                // Persist backfills on grid browse only; never write during export.
                if (!$isExport && (int) $quote->getStatus() === Status::APPROVED) {
                    $updates[$quoteId] = $reservedOrderId;
                }
            }

            $quote->setData('reserved_order_id', $reservedOrderId);
        }

        if ($updates) {
            $this->persistReservedOrderIds($updates);
        }

        return $items;
    }

    /**
     * @param array<int, string> $updates entity_id => reserved_order_id
     */
    private function persistReservedOrderIds(array $updates): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('quote');

        foreach ($updates as $entityId => $reservedOrderId) {
            $connection->update(
                $table,
                ['reserved_order_id' => $reservedOrderId],
                ['entity_id = ?' => (int) $entityId]
            );
        }
    }

    private function isExportRequest(): bool
    {
        $action = (string) $this->request->getFullActionName();

        return $action === 'mui_export_gridToCsv'
            || $action === 'mui_export_gridToXml';
    }
}
