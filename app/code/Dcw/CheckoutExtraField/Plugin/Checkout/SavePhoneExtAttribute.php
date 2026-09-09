<?php
namespace Dcw\CheckoutExtraField\Plugin\Checkout;

use Magento\Quote\Model\QuoteRepository;

class SavePhoneExtAttribute
{
protected $quoteRepository;

public function __construct(QuoteRepository $quoteRepository)
{
$this->quoteRepository = $quoteRepository;
}

public function beforeSaveAddressInformation(
\Magento\Checkout\Model\ShippingInformationManagement $subject,
$cartId,
\Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
) {
    
    if(!$extAttributes = $addressInformation->getExtensionAttributes())
    {
        return;
    }
        $quote = $this->quoteRepository->getActive($cartId);
        $quote->setPhoneExt($extAttributes->getPhoneExt());
        $address = $addressInformation->getShippingAddress();
        $address->setPhoneExt($extAttributes->getPhoneExt());

        // $phoneExt = $addressInformation->getShippingAddress()->getExtensionAttributes()->getPhoneExt();
        // $quote = $this->quoteRepository->getActive($cartId);
        // $quote->getShippingAddress()->setPhoneExt($phoneExt);
        // $result = $proceed($cartId, $addressInformation);
        // return $result;

    }
}