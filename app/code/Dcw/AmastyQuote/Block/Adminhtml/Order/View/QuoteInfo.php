<?php
declare(strict_types=1);

namespace Dcw\AmastyQuote\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Block\Adminhtml\Order\View\Info as OrderInfo;

class QuoteInfo extends Template
{
    public function __construct(
        Context $context,
        private readonly ResourceConnection $resource,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getOrder(): ?OrderInterface
    {
        $parent = $this->getParentBlock();
        if ($parent instanceof OrderInfo) {
            return $parent->getOrder();
        }

        return null;
    }

    public function getAmastyQuoteIncrementId(): string
    {
        $order = $this->getOrder();
		if (!$order) {
			return '';
		}

		$connection = $this->resource->getConnection();

		/**
		 * Get all quote IDs for the current order increment ID
		 */
		$quoteIds = $connection->fetchCol(
			$connection->select()
				->from(
					$this->resource->getTableName('quote'),
					['entity_id']
				)
				->where('reserved_order_id = ?', $order->getIncrementId())
		);

		if (empty($quoteIds)) {
			return '';
		}

		/**
		 * Get Amasty Quote Increment ID
		 */
		$incrementId = $connection->fetchOne(
			$connection->select()
				->from(
					$this->resource->getTableName('amasty_quote'),
					['increment_id']
				)
				->where('quote_id IN (?)', $quoteIds)
				->order('quote_id DESC')
				->limit(1)
		);

		return $incrementId ? (string)$incrementId : '';
    }

    public function shouldDisplay(): bool
    {
        return $this->getAmastyQuoteIncrementId() !== '';
    }
}
