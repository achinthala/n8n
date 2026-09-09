<?php

declare(strict_types=1);

namespace Dcw\Faq\Plugin;

use Amasty\Faq\Block\Lists\Pager;

class SearchPagerProductIdPlugin
{
    /**
     * Preserve product_id in FAQ search pagination URLs.
     */
    public function beforeGetPagerUrl(Pager $subject, array $params = []): array
    {
        $productId = (int)$subject->getRequest()->getParam('product_id');
        if ($productId) {
            $params['product_id'] = $productId;
        }

        return [$params];
    }
}
