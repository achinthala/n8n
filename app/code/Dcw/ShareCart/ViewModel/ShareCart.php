<?php
declare(strict_types=1);

namespace Dcw\ShareCart\ViewModel;

use Dcw\ShareCart\Model\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class ShareCart implements ArgumentInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly CheckoutSession $checkoutSession,
        private readonly CustomerSession $customerSession,
        private readonly FormKey $formKey,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    public function canShare(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $quote = $this->checkoutSession->getQuote();

        return $quote && $quote->getItemsCount() > 0;
    }

    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    public function getSubmitUrl(): string
    {
        return $this->urlBuilder->getUrl('sharecart/share/submit');
    }

    public function getPrefillYourName(): string
    {
        if (!$this->customerSession->isLoggedIn()) {
            return '';
        }

        return trim((string) $this->customerSession->getCustomer()->getName());
    }
}
