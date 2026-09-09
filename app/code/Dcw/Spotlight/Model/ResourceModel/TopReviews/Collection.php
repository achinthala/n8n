<?php
/**
 * Top Reviews Collection
 *
 * @category Dcw
 * @package  Dcw_Spotlight
 */
declare(strict_types=1);

namespace Dcw\Spotlight\Model\ResourceModel\TopReviews;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Dcw\Spotlight\Model\TopReviews as Model;
use Dcw\Spotlight\Model\ResourceModel\TopReviews as ResourceModel;

/**
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'dcw_spotlight_top_reviews_collection';

    /**
     * Initialize collection model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}

