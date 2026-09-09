<?php

declare(strict_types=1);

namespace Dcw\Faq\Block\Lists;

use Amasty\Faq\Block\Lists\QuestionsList as AmastyQuestionsList;

class QuestionsList extends AmastyQuestionsList
{
    /**
     * Apply product filter on search results when product_id is present in the request.
     */
    protected function generateSearchResult()
    {
        parent::generateSearchResult();

        $productId = (int)$this->getRequest()->getParam('product_id');
        if ($productId && $this->collection) {
            $this->collection->addProductFilter($productId);
        }
    }
}
