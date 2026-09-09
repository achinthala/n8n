<?php
/**
 * Related products slider block with theme-aware cache key
 */
declare(strict_types=1);

namespace Dcw\Custom\Block\Product;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\DesignInterface;

class RelatedSlider extends Template
{
    /**
     * @var DesignInterface
     */
    private $design;

    /**
     * @param Context $context
     * @param DesignInterface $design
     * @param array $data
     */
    public function __construct(
        Context $context,
        DesignInterface $design,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->design = $design;
    }

    /**
     * Get current theme path for cache key (e.g. frontend/Dcw/hyvamobile)
     *
     * @return string
     */
    public function getThemeCacheKey(): string
    {
        return (string) $this->design->getDesignTheme()->getThemePath();
    }
}
