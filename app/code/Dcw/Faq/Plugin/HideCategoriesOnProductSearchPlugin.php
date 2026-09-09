<?php

declare(strict_types=1);

namespace Dcw\Faq\Plugin;

use Amasty\Faq\Block\Lists\CategoryList;

class HideCategoriesOnProductSearchPlugin
{
    /**
     * Hide global FAQ categories when search is scoped to a product.
     */
    public function aroundToHtml(CategoryList $subject, callable $proceed): string
    {
        if ((int)$subject->getRequest()->getParam('product_id')) {
            return '';
        }

        return $proceed();
    }
}
