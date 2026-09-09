<?php

namespace Dcw\CustomCategoryAttribute\Block;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Category;

class BottomContent extends Template
{
    protected $category;

    public function __construct(
        Template\Context $context,
        Category $category,
        array $data = []
    ) {
        $this->category = $category;
        parent::__construct($context, $data);
    }

    public function getCategoryBottomContent()
    {
        $category = $this->category->load($this->getCurrentCategoryId());
        return $category->getData('category_bottom_content');
    }

    public function getCurrentCategoryId()
    {
        return $this->getRequest()->getParam('id');
    }
}
