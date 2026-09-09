<?php

namespace Dcw\Faq\Plugin;

use Amasty\Faq\Block\Lists\QuestionsList;

class ProductQuestionSortPlugin
{
    public function afterGetCollection(
        QuestionsList $subject,
        $collection
    ) {
        // Apply only for PDP
		if ($subject->getRequest()->getFullActionName() === 'catalog_product_view') {
			$collection->setOrder('positive_rating', 'DESC');
		}

        return $collection;
    }
}

