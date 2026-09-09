<?php

declare(strict_types=1);

namespace Dcw\SplitPayment\Model;

/**
 * Split Payment method (multi-instrument checkout; admin orders may be held for review).
 */
class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    public const METHOD_CODE = 'splitpayment';

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;
}
