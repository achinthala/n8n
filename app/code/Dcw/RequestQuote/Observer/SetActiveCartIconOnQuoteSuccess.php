<?php
/**
 * Show default cart icon after quote submit (Checkout Later, Save Cart).
 * Quote was saved - user returns to normal flow.
 */

declare(strict_types=1);

namespace Dcw\RequestQuote\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Dcw\RequestQuote\ViewModel\ActiveCartIcon;
use Psr\Log\LoggerInterface;

class SetActiveCartIconOnQuoteSuccess implements ObserverInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CheckoutSession $checkoutSession
     * @param LoggerInterface $logger
     */
    public function __construct(CheckoutSession $checkoutSession, LoggerInterface $logger)
    {
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        $this->checkoutSession->setData(ActiveCartIcon::getSessionKey(), ActiveCartIcon::getTypeCart());
        $this->logger->info('SetActiveCartIconOnQuoteSuccess: Set dcw_active_cart_icon to cart', [
            'session_key' => ActiveCartIcon::getSessionKey(),
            'value' => ActiveCartIcon::getTypeCart(),
        ]);
    }
}
