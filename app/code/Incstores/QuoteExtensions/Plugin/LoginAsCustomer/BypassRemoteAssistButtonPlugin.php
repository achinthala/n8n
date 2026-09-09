<?php
/**
 * Copyright © Incstores. All rights reserved.
 */
declare(strict_types=1);

namespace Incstores\QuoteExtensions\Plugin\LoginAsCustomer;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\LoginAsCustomerAdminUi\Ui\Customer\Component\Button\DataProvider;

/**
 * Plugin to bypass Remote Assist check on Login as Customer button
 *
 * This plugin ensures the button always works regardless of the customer's
 * "Enable Remote Assist" setting by restoring the proper onclick behavior
 * if it was changed to the "not allowed" popup.
 */
class BypassRemoteAssistButtonPlugin
{
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param Escaper $escaper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Escaper $escaper,
        UrlInterface $urlBuilder
    ) {
        $this->escaper = $escaper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Restore proper button behavior after assistance check
     *
     * This runs after the LoginAsCustomerButtonDataProviderPlugin from the
     * assistance module. If that plugin changed the onclick to show a "not allowed"
     * popup, we restore it to the proper confirmation popup.
     *
     * @param DataProvider $subject
     * @param array $result
     * @param int $customerId
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetData(DataProvider $subject, array $result, int $customerId): array
    {
        // If the assistance plugin changed the onclick to the "not allowed" popup,
        // restore it to the proper confirmation popup
        if (isset($result['on_click']) && $result['on_click'] === 'window.lacNotAllowedPopup()') {
            $loginUrl = $this->getLoginUrl($customerId);
            $result['on_click'] = 'window.lacConfirmationPopup("'
                . $this->escaper->escapeHtml($this->escaper->escapeJs($loginUrl))
                . '")';
        }

        return $result;
    }

    /**
     * Get Login as Customer login url
     *
     * @param int $customerId
     * @return string
     */
    private function getLoginUrl(int $customerId): string
    {
        return $this->urlBuilder->getUrl('loginascustomer/login/login', ['customer_id' => $customerId]);
    }
}
