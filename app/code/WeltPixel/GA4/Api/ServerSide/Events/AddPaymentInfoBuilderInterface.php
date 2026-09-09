<?php

namespace WeltPixel\GA4\Api\ServerSide\Events;

interface AddPaymentInfoBuilderInterface
{
    /**
     * @param \Magento\Sales\Model\Order $order
     * @param string $paymentType
     * @return null|AddPaymentInfoInterface
     */
    function getAddPaymentInfoEvent($order, $paymentType);
}
