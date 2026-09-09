<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package FAQ and Product Questions for Magento 2
 */

namespace Dcw\Faq\Block\Widgets;

use Amasty\Faq\Api\Data\CategoryInterface;
use Amasty\Faq\Api\Data\QuestionInterface;
use Amasty\Faq\Block\RichData\StructuredData;
use Amasty\Faq\Model\ConfigProvider;
use Amasty\Faq\Model\ResourceModel\Category\Collection;
use Amasty\Faq\Model\ResourceModel\Category\CollectionFactory;
use Amasty\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Amasty\Faq\Model\Url;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

class Categories extends \Amasty\Faq\Block\Widgets\Categories
{
    /**
     * @return int
     */
    public function getQuestionsLimit()
    {
        $this->setData('questions_limit', 30);

        if (!$this->hasData('questions_limit')) {
            $this->setData('questions_limit', 0);
        }

        return (int)$this->getData('questions_limit');
    }
}
