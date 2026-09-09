<?php

declare(strict_types=1);

namespace Dcw\Checkout\Plugin\Checkout;

use Dcw\Checkout\Model\Validator\TelephoneDigitsValidator;
use Magento\Checkout\Model\PaymentInformationManagement;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteRepository;

class PaymentInformationManagementPlugin
{
    public function __construct(
        private readonly TelephoneDigitsValidator $telephoneDigitsValidator,
		private readonly QuoteRepository $quoteRepository
    ) {
    }

    /**
     * @param int $cartId
     */
    public function beforeSavePaymentInformation(
        PaymentInformationManagement $subject,
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): void {
        if ($billingAddress !== null) {
            $this->telephoneDigitsValidator->assertMinDigits($billingAddress->getTelephone());
        }
    }
	
	/**
	 * Validate that the shipping address contains at least one numeric
	 * character before allowing the order to be placed.
	 *
	 * @param PaymentInformationManagement $subject
	 * @param int $cartId
	 * @param PaymentInterface $paymentMethod
	 * @param AddressInterface|null $billingAddress
	 * @return array
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function beforeSavePaymentInformationAndPlaceOrder(
		PaymentInformationManagement $subject,
		$cartId,
		PaymentInterface $paymentMethod,
		AddressInterface $billingAddress = null
	) {
		$quote = $this->quoteRepository->getActive($cartId);

		$street = implode(' ', $quote->getShippingAddress()->getStreet());

		if (!preg_match('/\d/', $street)) {
			throw new \Magento\Framework\Exception\LocalizedException(
				__('Shipping address must contain a number.')
			);
		}

		return [$cartId, $paymentMethod, $billingAddress];
	}
}
