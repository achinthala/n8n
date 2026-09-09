<?php
/**
 * Top Reviews Model
 *
 * @category Dcw
 * @package  Dcw_Spotlight
 */
declare(strict_types=1);

namespace Dcw\Spotlight\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class TopReviews
 */
class TopReviews extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'dcw_spotlight_top_reviews';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Dcw\Spotlight\Model\ResourceModel\TopReviews::class);
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
     * Get Review ID
     *
     * @return string|null
     */
    public function getReviewId()
    {
        return $this->getData('review_id');
    }

    /**
     * Set Review ID
     *
     * @param string $reviewId
     * @return $this
     */
    public function setReviewId(string $reviewId)
    {
        return $this->setData('review_id', $reviewId);
    }

    /**
     * Get Author Name
     *
     * @return string|null
     */
    public function getAuthorName()
    {
        return $this->getData('author_name');
    }

    /**
     * Set Author Name
     *
     * @param string $authorName
     * @return $this
     */
    public function setAuthorName(string $authorName)
    {
        return $this->setData('author_name', $authorName);
    }

    /**
     * Get Date Published
     *
     * @return string|null
     */
    public function getDatePublished()
    {
        return $this->getData('date_published');
    }

    /**
     * Set Date Published
     *
     * @param string $datePublished
     * @return $this
     */
    public function setDatePublished(string $datePublished)
    {
        return $this->setData('date_published', $datePublished);
    }

    /**
     * Get Review Title
     *
     * @return string|null
     */
    public function getReviewTitle()
    {
        return $this->getData('review_title');
    }

    /**
     * Set Review Title
     *
     * @param string $reviewTitle
     * @return $this
     */
    public function setReviewTitle(string $reviewTitle)
    {
        return $this->setData('review_title', $reviewTitle);
    }

    /**
     * Get Review Body
     *
     * @return string|null
     */
    public function getReviewBody()
    {
        return $this->getData('review_body');
    }

    /**
     * Set Review Body
     *
     * @param string $reviewBody
     * @return $this
     */
    public function setReviewBody(string $reviewBody)
    {
        return $this->setData('review_body', $reviewBody);
    }

    /**
     * Get Rating Value
     *
     * @return int|null
     */
    public function getRatingValue()
    {
        return $this->getData('rating_value');
    }

    /**
     * Set Rating Value
     *
     * @param int $ratingValue
     * @return $this
     */
    public function setRatingValue(int $ratingValue)
    {
        return $this->setData('rating_value', $ratingValue);
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

