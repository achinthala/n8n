<?php
/**
 * ViewModel to determine which cart icon to show (quote vs regular cart)
 * Uses session flag: 'quote' = show quote icon, 'cart' = show regular cart icon
 * On quote success (checkout later) we always show the default Magento cart icon.
 */

declare(strict_types=1);

namespace Dcw\RequestQuote\ViewModel;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class ActiveCartIcon implements ArgumentInterface
{
    private const SESSION_KEY = 'dcw_active_cart_icon';
    private const TYPE_QUOTE = 'quote';
    private const TYPE_CART = 'cart';

    /** @var string Full action name for Amasty quote success (checkout later) */
    private const QUOTE_SUCCESS_ACTION = 'amasty_quote_quote_success';

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param CheckoutSession $checkoutSession
     * @param RequestInterface $request
     */
    public function __construct(CheckoutSession $checkoutSession, RequestInterface $request)
    {
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
    }

    /**
     * Get active cart icon type: 'quote' or 'cart'
     * Defaults to 'cart' when not set. On quote success page always returns 'cart'.
     *
     * @return string
     */
    public function getActiveCartType(): string
    {
        if ($this->request->getFullActionName() === self::QUOTE_SUCCESS_ACTION) {
            return self::TYPE_CART;
        }
        $value = $this->checkoutSession->getData(self::SESSION_KEY);
        return ($value === self::TYPE_QUOTE) ? self::TYPE_QUOTE : self::TYPE_CART;
    }

    /**
     * Check if quote icon should be shown
     *
     * @return bool
     */
    public function isQuoteIconActive(): bool
    {
        return $this->getActiveCartType() === self::TYPE_QUOTE;
    }

    /**
     * Check if regular cart icon should be shown
     *
     * @return bool
     */
    public function isCartIconActive(): bool
    {
        return $this->getActiveCartType() === self::TYPE_CART;
    }

    /**
     * Session key for external use (e.g. setting the flag)
     *
     * @return string
     */
    public static function getSessionKey(): string
    {
        return self::SESSION_KEY;
    }

    /**
     * @return string
     */
    public static function getTypeQuote(): string
    {
        return self::TYPE_QUOTE;
    }

    /**
     * @return string
     */
    public static function getTypeCart(): string
    {
        return self::TYPE_CART;
    }
}
