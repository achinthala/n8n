<?php
/**
 * Observer to set active cart icon to 'cart' when product is added to cart
 */

declare(strict_types=1);

namespace Dcw\RequestQuote\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Dcw\RequestQuote\ViewModel\ActiveCartIcon;

class SetActiveCartIconOnAddToCart implements ObserverInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Set active cart icon to 'cart' when product is added to cart
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $this->checkoutSession->setData(ActiveCartIcon::getSessionKey(), ActiveCartIcon::getTypeCart());
    }
}
