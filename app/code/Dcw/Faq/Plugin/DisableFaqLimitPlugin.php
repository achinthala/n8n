<?php

namespace Dcw\Faq\Plugin;

use Amasty\Faq\Block\Lists\QuestionsList;

class DisableFaqLimitPlugin
{
    public function afterGetLimit(QuestionsList $subject, $result)
    {
        // Only on PDP
        if ($subject->getRequest()->getFullActionName() !== 'catalog_product_view') {
            return $result;
        }

        // Allow frontend JS load-more to control pagination
        return null; // 👈 disables setCurPage + setPageSize
    }
}
