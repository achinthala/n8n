<?php

declare(strict_types=1);

namespace Dcw\PaymentMethods\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;

/**
 * Order statuses allowed for Check / Money Order "New Order Status" (pending + processing).
 */
class NewAndProcessing extends Status
{
    /**
     * @var string[]
     */
    protected $_stateStatuses = [
        Order::STATE_NEW,
        Order::STATE_PROCESSING,
    ];
}
