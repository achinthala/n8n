<?php
declare(strict_types=1);

namespace Dcw\ShareCart\ViewModel;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class ShareCartRestore implements ArgumentInterface
{
    public const HASH_TOKEN_PARAM = 'token';

    public function __construct(
        private readonly FormKey $formKey,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    public function getHashTokenParam(): string
    {
        return self::HASH_TOKEN_PARAM;
    }

    public function getApplyUrl(): string
    {
        return $this->urlBuilder->getUrl('sharecart/cart/apply');
    }

    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }
}
