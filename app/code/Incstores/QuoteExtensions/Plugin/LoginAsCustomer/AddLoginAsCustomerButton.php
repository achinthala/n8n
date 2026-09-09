<?php
/**
 * Copyright © Incstores. All rights reserved.
 */
declare(strict_types=1);

namespace Incstores\QuoteExtensions\Plugin\LoginAsCustomer;

use Amasty\RequestQuote\Block\Adminhtml\Quote\View;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\LoginAsCustomerApi\Api\ConfigInterface;

/**
 * Plugin to add Login as Customer button to Amasty Quote View toolbar
 */
class AddLoginAsCustomerButton
{
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param AuthorizationInterface $authorization
     * @param ConfigInterface $config
     * @param Escaper $escaper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        AuthorizationInterface $authorization,
        ConfigInterface $config,
        Escaper $escaper,
        UrlInterface $urlBuilder
    ) {
        $this->authorization = $authorization;
        $this->config = $config;
        $this->escaper = $escaper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Add Login as Customer button before layout is set
     *
     * @param View $subject
     * @return void
     */
    public function beforeSetLayout(View $subject): void
    {
        $quote = $subject->getQuote();

        // Check if we have a valid quote with a customer
        if (!$quote || !$quote->getCustomerId() || $quote->getCustomerIsGuest()) {
            return;
        }

        // Check if Login as Customer is enabled and admin has permission
        if (!$this->config->isEnabled()
            || !$this->authorization->isAllowed('Magento_LoginAsCustomer::login')
        ) {
            return;
        }

        $customerId = (int)$quote->getCustomerId();
        $loginUrl = $this->getLoginUrl($customerId);

        // Add the Login as Customer button
        $subject->addButton(
            'login_as_customer',
            [
                'label' => __('Login as Customer'),
                'onclick' => 'window.lacConfirmationPopup("'
                    . $this->escaper->escapeHtml($this->escaper->escapeJs($loginUrl))
                    . '")',
                'class' => 'login-as-customer',
            ],
            -1 // Position at the beginning
        );
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
