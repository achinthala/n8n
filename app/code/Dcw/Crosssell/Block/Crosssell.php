<?php

namespace Dcw\Crosssell\Block;

class Crosssell extends \Magento\Framework\View\Element\Template {

    protected $_registry;

    public function __construct(
    \Magento\Framework\View\Element\Template\Context $context, \Magento\Framework\Registry $registry, array $data = []
    ) {
        $this->_registry = $registry;

        parent::__construct($context, $data);
    }

    public function getCurrentProduct() {
        return $this->_registry->registry('current_product');
    }

    public function getItems() {
        $items = $this->getData('items');
        if ($items === null) {
            $product = $this->getCurrentProduct();
            $items = $product->getCrossSellProducts();
            $this->setData('items', $items);
        }
        return $items;
    }

    public function getItemCount() {
        return count($this->getItems());
    }

}