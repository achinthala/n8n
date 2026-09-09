<?php
/**
 * Product Ratings Resource Model
 *
 * @category Dcw
 * @package  Dcw_Spotlight
 */
declare(strict_types=1);

namespace Dcw\Spotlight\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class ProductRatings
 */
class ProductRatings extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'dcw_spotlight_product_ratings_resource_model';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('dcw_spotlight_product_ratings', 'id');
        $this->_useIsObjectNew = true;
    }
}

