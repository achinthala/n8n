<?php
/**
 * Top Reviews Resource Model
 *
 * @category Dcw
 * @package  Dcw_Spotlight
 */
declare(strict_types=1);

namespace Dcw\Spotlight\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class TopReviews
 */
class TopReviews extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'dcw_spotlight_top_reviews_resource_model';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('dcw_spotlight_top_reviews', 'id');
        $this->_useIsObjectNew = true;
    }
}

