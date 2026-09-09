<?php
namespace Dcw\ShoppingCart\Plugin;

use Magento\Checkout\Model\GuestPaymentInformationManagement;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

class GuestPaymentInformationManagementPlugin
{

    /**
     * @var SubscriptionManagerInterface
     */
    private $subscriptionManager;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param RequestInterface $request
     */
    public function __construct(
        SubscriptionManagerInterface $subscriptionManager,
        StoreManagerInterface $storeManager
    ) {
        $this->subscriptionManager = $subscriptionManager;
        $this->_storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function afterSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagement $subject,
        $cartId,
        $result,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {

        try {
            $isSubscribed = $paymentMethod->getExtensionAttributes()->getIsSubscribed();

            // Check if newsletter subscription checkbox was checked
            if ($isSubscribed) {
                $storeId = (int)$this->_storeManager->getStore()->getId();
                $this->subscriptionManager->subscribe($email, $storeId);
            }
        } catch (\Exception $e) {

        }

        return $result;
    }
}
