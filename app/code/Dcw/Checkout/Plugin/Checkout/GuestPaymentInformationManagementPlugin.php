<?php

namespace Dcw\Checkout\Plugin\Checkout;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Checkout\Model\GuestPaymentInformationManagement;
use Magento\Quote\Model\QuoteIdMaskFactory;

class GuestPaymentInformationManagementPlugin
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;
	
	/**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
		QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * Validate shipping address before placing order for guest customers.
     *
     * @param \Magento\Checkout\Model\GuestPaymentInformationManagement $subject
     * @param string $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     *
     * @return array
     * @throws LocalizedException
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagement $subject,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

		$quote = $this->quoteRepository->getActive(
			(int) $quoteIdMask->getQuoteId()
		);

        $shippingAddress = $quote->getShippingAddress();
        $street = implode(' ', $shippingAddress->getStreet() ?? []);

        if (!preg_match('/\d/', $street)) {
            throw new LocalizedException(
                __('Shipping address must contain a number.')
            );
        }

        return [$cartId, $email, $paymentMethod, $billingAddress];
    }
}