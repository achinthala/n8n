<?php

declare(strict_types=1);

namespace Dcw\Faq\Block\Catalog\Product;

use Amasty\Faq\Model\ConfigProvider;
use Magento\Framework\View\Element\Template;

class QuestionsAnchorLink extends Template
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        Template\Context $context,
        ConfigProvider $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
    }

    public function isVisible(): bool
    {
        return $this->configProvider->isEnabled() && $this->configProvider->isShowTab();
    }
}
