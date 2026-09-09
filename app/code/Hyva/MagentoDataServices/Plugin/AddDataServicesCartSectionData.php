<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */

declare(strict_types=1);

namespace Hyva\MagentoDataServices\Plugin;

use Magento\Checkout\CustomerData\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Serialize\Serializer\Base64Json;
use Magento\Framework\Stdlib\CookieManagerInterface;

class AddDataServicesCartSectionData
{
    private CheckoutSession $checkoutSession;
    private Base64Json $base64JsonSerializer;
    private CookieManagerInterface $cookieManager;

    public function __construct(
        CheckoutSession $checkoutSession,
        Base64Json $base64JsonSerializer,
        CookieManagerInterface $cookieManager
    ) {

        $this->checkoutSession = $checkoutSession;
        $this->base64JsonSerializer = $base64JsonSerializer;
        $this->cookieManager = $cookieManager;
    }
    public function afterGetSectionData(Cart $subject, array $result): array
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            $totals = $quote->getTotals();
            $subtotal = $totals['subtotal'] ?? null;

            $result['dsCartId'] = $quote->getId();
            if ($subtotal) {
                $result['subtotalAmountExclTax'] = (float)$subtotal->getValueExclTax();
            }

            $productContextCookie = $this->cookieManager->getCookie('dataservices_product_context');

            if ($productContextCookie) {
                try {
                    $deserializedProductContext = $this->base64JsonSerializer->unserialize($productContextCookie);
                } catch (\Exception $e) {
                    $deserializedProductContext = [];
                }
            }

            $result['dataservices_product_context'] = $deserializedProductContext ?? [];
        } catch (\Exception $e) {
            // If quote loading fails (e.g., customer deleted), return empty data
            $result['dsCartId'] = null;
            $result['subtotalAmountExclTax'] = 0;
            $result['dataservices_product_context'] = [];
        }
        
        return $result;
    }
}
