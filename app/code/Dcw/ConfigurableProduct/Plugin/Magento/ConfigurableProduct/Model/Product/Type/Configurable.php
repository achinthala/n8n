<?php

declare(strict_types=1);

namespace Dcw\ConfigurableProduct\Plugin\Magento\ConfigurableProduct\Model\Product\Type;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;

class Configurable
{
    /**
     * Do not resolve disabled associated simples for cart, wishlist, or share-cart restore.
     *
     * Magento's getProductByAttributes() uses the used-product collection, which still
     * includes Disabled children. Those children can then be added via super_attribute.
     *
     * @param ConfigurableType $subject
     * @param Product|null $result
     * @return Product|null
     */
    public function afterGetProductByAttributes(ConfigurableType $subject, $result)
    {
        if (!$result instanceof Product || !$result->getId()) {
            return $result;
        }

        if ((int) $result->getStatus() !== Status::STATUS_ENABLED) {
            return null;
        }

        return $result;
    }
}
