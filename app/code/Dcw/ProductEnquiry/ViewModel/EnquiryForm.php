<?php
declare(strict_types=1);

namespace Dcw\ProductEnquiry\ViewModel;

use Dcw\ProductEnquiry\Model\Config;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class EnquiryForm implements ArgumentInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly CatalogHelper $catalogHelper,
        private readonly CustomerSession $customerSession,
        private readonly FormKey $formKey,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    public function canShow(): bool
    {
        return $this->isEnabled() && $this->getProduct() !== null;
    }

    public function getProduct(): ?ProductInterface
    {
        $product = $this->catalogHelper->getProduct();

        return $product ?: null;
    }

    public function getProductId(): int
    {
        $product = $this->getProduct();

        return $product ? (int) $product->getId() : 0;
    }

    public function getProductSku(): string
    {
        $product = $this->getProduct();

        return $product ? (string) $product->getSku() : '';
    }

    public function getProductName(): string
    {
        $product = $this->getProduct();

        return $product ? (string) $product->getName() : '';
    }

    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    public function getSubmitUrl(): string
    {
        return $this->urlBuilder->getUrl('productenquiry/index/submit');
    }

    public function getFormTitle(): string
    {
        return $this->config->getFormTitle();
    }

    public function getPrefillName(): string
    {
        if (!$this->customerSession->isLoggedIn()) {
            return '';
        }

        return trim((string) $this->customerSession->getCustomer()->getName());
    }

    public function getPrefillEmail(): string
    {
        if (!$this->customerSession->isLoggedIn()) {
            return '';
        }

        return trim((string) $this->customerSession->getCustomer()->getEmail());
    }
}
