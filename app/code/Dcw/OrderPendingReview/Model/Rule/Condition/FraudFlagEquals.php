<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Model\Rule\Condition;

use Dcw\OrderPendingReview\Model\FraudFlag;
use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Model\Quote\Address;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Context;

/**
 * Match when quote.dcw_fraud_flag equals the selected value (1 or 2).
 */
class FraudFlagEquals extends AbstractCondition
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
            'dcw_fraud_flag' => __('Fraud flag'),
        ]);
        return $this;
    }

    public function loadValueOptions()
    {
        $this->setValueOption([
            (string) FraudFlag::ADDRESS_MISMATCH_1K => __(
                '%1 — $1K+ with different billing/shipping state',
                FraudFlag::ADDRESS_MISMATCH_1K
            ),
            (string) FraudFlag::FAILED_PAYMENTS => __(
                '%1 — %2+ failed payment attempts',
                FraudFlag::FAILED_PAYMENTS,
                FraudFlag::minFailedAttempts()
            ),
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
        $flag = (int) $quote->getData('dcw_fraud_flag');
        $expected = (int) $this->getValue();

        return $expected > 0 && $flag === $expected;
    }
}
