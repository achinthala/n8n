<?php
/**
 * Product Ratings Model
 *
 * @category Dcw
 * @package  Dcw_Spotlight
 */
declare(strict_types=1);

namespace Dcw\Spotlight\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class ProductRatings
 */
class ProductRatings extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'dcw_spotlight_product_ratings';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Dcw\Spotlight\Model\ResourceModel\ProductRatings::class);
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getData('id');
    }

    /**
     * Get SKU
     *
     * @return string|null
     */
    public function getSku()
    {
        return $this->getData('sku');
    }

    /**
     * Set SKU
     *
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku)
    {
        return $this->setData('sku', $sku);
    }

    /**
     * Get Average Rating Value
     *
     * @return float|null
     */
    public function getAvgRatingValue()
    {
        return $this->getData('avg_rating_value');
    }

    /**
     * Set Average Rating Value
     *
     * @param float $avgRatingValue
     * @return $this
     */
    public function setAvgRatingValue(float $avgRatingValue)
    {
        return $this->setData('avg_rating_value', $avgRatingValue);
    }

    /**
     * Get Reviews Count
     *
     * @return int|null
     */
    public function getReviewsCount()
    {
        return $this->getData('reviews_count');
    }

    /**
     * Set Reviews Count
     *
     * @param int $reviewsCount
     * @return $this
     */
    public function setReviewsCount(int $reviewsCount)
    {
        return $this->setData('reviews_count', $reviewsCount);
    }

    /**
     * Get Best Rating
     *
     * @return int|null
     */
    public function getBestRating()
    {
        return $this->getData('best_rating');
    }

    /**
     * Set Best Rating
     *
     * @param int $bestRating
     * @return $this
     */
    public function setBestRating(int $bestRating)
    {
        return $this->setData('best_rating', $bestRating);
    }

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    /**
     * Get Updated At
     *
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData('updated_at');
    }
}

