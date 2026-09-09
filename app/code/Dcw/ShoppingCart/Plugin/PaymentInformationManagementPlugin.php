<?php
namespace Dcw\ShoppingCart\Plugin;

use Magento\Checkout\Model\PaymentInformationManagement;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

class PaymentInformationManagementPlugin
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
     * @var Session
     */
    private $session;

    /**
     * @param RequestInterface $request
     */
    public function __construct(
        SubscriptionManagerInterface $subscriptionManager,
        StoreManagerInterface $storeManager,
        Session $session
    ) {
        $this->subscriptionManager = $subscriptionManager;
        $this->_storeManager = $storeManager;
        $this->session = $session;
    }

    /**
     * @param PaymentInformationManagement $subject
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return array
     */
    public function afterSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagement $subject,
        $result,
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        try {
            $isSubscribed = $paymentMethod->getExtensionAttributes()->getIsSubscribed();

            // Check if newsletter subscription checkbox was checked
            if ($isSubscribed) {
                $email = $billingAddress->getEmail();
                if (!$email) {
                    $email = $this->session->getQuote()->getCustomer()->getEmail();
                }

                $storeId = (int)$this->_storeManager->getStore()->getId();
                $currentCustomerId = $this->session->getQuote()->getCustomerId();
                $this->subscriptionManager->subscribeCustomer($currentCustomerId, $storeId);
            }
        } catch (\Exception $e) {

        }

        return $result;
    }
}
