<?php
/**
 * Override Amasty FAQ Search Block to add product ID support
 */
namespace Dcw\Faq\Block\Forms;

use Amasty\Faq\Block\Forms\Search as AmastySearch;
use Magento\Framework\Registry;

class Search extends AmastySearch
{
    /**
     * @var Registry
     */
    private $registry;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Amasty\Faq\Model\ConfigProvider $configProvider,
        Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $configProvider, $data);
    }

    /**
     * Get product ID from the current product or request (for product-scoped search).
     *
     * @return int|null
     */
    public function getCurrentProductId()
    {
        $product = $this->registry->registry('current_product');
        if ($product) {
            return (int)$product->getId();
        }

        $productId = (int)$this->getRequest()->getParam('product_id');

        return $productId ?: null;
    }

    /**
     * Check if we're on a product page
     *
     * @return bool
     */
    public function isProductPage()
    {
        return $this->registry->registry('current_product') !== null;
    }
}

