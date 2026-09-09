<?php

declare(strict_types=1);

namespace Dcw\ConfigurableProduct\Plugin\Magento\Quote\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote as QuoteModel;

class Quote
{
    /**
     * Block adding a configurable when the selected associated simple is Disabled.
     *
     * Quote::addProduct() only checks that the parent is salable.
     *
     * @param QuoteModel $subject
     * @param Product $product
     * @param mixed $request
     * @param string|null $processMode
     * @return void
     * @throws LocalizedException
     */
    public function beforeAddProduct(
        QuoteModel $subject,
        Product $product,
        $request = null,
        $processMode = null
    ): void {
        if ($product->getTypeId() !== Configurable::TYPE_CODE) {
            return;
        }

        if (!$request instanceof DataObject) {
            return;
        }

        $superAttribute = $request->getData('super_attribute');
        if (!is_array($superAttribute) || $superAttribute === []) {
            return;
        }

        $child = $product->getTypeInstance()->getProductByAttributes($superAttribute, $product);
        if (!$child instanceof Product || !$child->getId()) {
            throw new LocalizedException(__('This product is not available.'));
        }

        if ((int) $child->getStatus() !== Status::STATUS_ENABLED) {
            throw new LocalizedException(__('This product is not available.'));
        }
    }
}
