<?php

declare(strict_types=1);

namespace Dcw\Faq\Plugin;

use Amasty\Faq\Block\Forms\AskQuestion;

class AskQuestionProductIdPlugin
{
    /**
     * Associate submitted questions with the product when searching from a PDP.
     */
    public function afterGetAdditionalField(AskQuestion $subject, ?array $result): ?array
    {
        if ($result !== null) {
            return $result;
        }

        $productId = (int)$subject->getRequest()->getParam('product_id');
        if ($productId) {
            return ['field' => 'product_ids', 'value' => $productId];
        }

        return $result;
    }
}
