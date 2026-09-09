<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Plugin\Quote;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\CartInterface;

class CartRepositoryPlugin
{
    public function __construct(
        private readonly CartExtensionFactory $cartExtensionFactory
    ) {
    }

    public function afterGet(CartRepositoryInterface $subject, CartInterface $quote): CartInterface
    {
        $this->hydrate($quote);
        return $quote;
    }

    public function afterGetList(
        CartRepositoryInterface $subject,
        \Magento\Quote\Api\Data\CartSearchResultsInterface $searchResult
    ): \Magento\Quote\Api\Data\CartSearchResultsInterface {
        foreach ($searchResult->getItems() as $quote) {
            $this->hydrate($quote);
        }
        return $searchResult;
    }

    private function hydrate(CartInterface $quote): void
    {
        $ext = $quote->getExtensionAttributes();
        if ($ext === null) {
            $ext = $this->cartExtensionFactory->create();
        }
        $ext->setDcwFailedPaymentAttempts((int) $quote->getData('dcw_failed_payment_attempts'));
        $ext->setDcwFraudFlag((int) $quote->getData('dcw_fraud_flag'));
        $quote->setExtensionAttributes($ext);
    }
}
