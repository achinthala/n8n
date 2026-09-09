<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Block\Adminhtml\Order\View;

use Dcw\OrderPendingReview\Model\ReasonDisplayFormatter;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Block\Adminhtml\Order\View\Info as OrderInfo;

class PendingReviewReasons extends Template
{
    public function __construct(
        Context $context,
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

    public function getReasonsDisplayText(): string
    {
        $order = $this->getOrder();
        if (!$order) {
            return '';
        }
        $json = (string) $order->getData('dcw_pending_review_reasons');

        return ReasonDisplayFormatter::formatJson($json !== '' ? $json : null);
    }

    public function shouldDisplay(): bool
    {
        return $this->getReasonsDisplayText() !== '';
    }
}
