<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Model\Rule\Condition;

use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Model\Quote\Address;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Context;

/**
 * Match when quote payment method equals the selected gateway code (evaluated on quote address like other cart conditions).
 */
class PaymentMethodEquals extends AbstractCondition
{
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setType(self::class);
    }

    public function loadAttributeOptions()
    {
        $this->setAttributeOption([
            'payment_method' => __('Payment method'),
        ]);

        return $this;
    }

    public function loadValueOptions()
    {
        $this->setValueOption([
            'dcw_po_gateway' => __('Purchase Order (dcw_po_gateway)'),
            'splitpayment' => __('Split Payment (splitpayment)'),
        ]);

        return $this;
    }

    public function validate(AbstractModel $model): bool
    {
        if (!$model instanceof Address) {
            return false;
        }
        $quote = $model->getQuote();
        if (!$quote || !$quote->getId()) {
            return false;
        }
        $payment = $quote->getPayment();
        if (!$payment || !$payment->getMethod()) {
            return false;
        }
        $expected = (string) $this->getValue();

        return $expected !== '' && $payment->getMethod() === $expected;
    }
}
