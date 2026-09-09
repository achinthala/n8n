<?php

declare(strict_types=1);

namespace Dcw\Checkout\Plugin\Checkout;

use Dcw\Checkout\Model\Validator\TelephoneDigitsValidator;
use Dcw\Checkout\Model\Validator\StreetNumberValidator;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;

class ShippingInformationManagementPlugin
{
    public function __construct(
        private readonly TelephoneDigitsValidator $telephoneDigitsValidator,
		private readonly StreetNumberValidator $streetNumberValidator
    ) {
    }

    /**
     * @param int $cartId
     */
    public function beforeSaveAddressInformation(
        ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ): void {
		$this->createLog('beforeSaveAddressInformation started');
        $shipping = $addressInformation->getShippingAddress();
        if ($shipping) {
			$this->createLog('shipping');
            $this->telephoneDigitsValidator->assertMinDigits($shipping->getTelephone());
			$this->streetNumberValidator->validate($shipping->getStreet() ?? []);
        }
        $billing = $addressInformation->getBillingAddress();
        if ($billing) {
            $this->telephoneDigitsValidator->assertMinDigits($billing->getTelephone());
        }
    }
	public function createLog($msg)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/beforeSaveAddressInformation.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
}
