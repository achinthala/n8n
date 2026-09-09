<?php
namespace Dcw\LineItemUpdateESD\Model\Data;

use Magento\Framework\DataObject;
use Dcw\LineItemUpdateESD\Api\Data\UpdateItemResponseInterface;

/**
 * Response model for Update Item API
 */
class UpdateItemResponse extends DataObject implements UpdateItemResponseInterface
{
    /**
     * Get status
     *
     * @return bool
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Set status
     *
     * @param bool $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * Set message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * Get order item ID
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->getData(self::ITEM_ID);
    }

    /**
     * Set order item ID
     *
     * @param int $itemId
     * @return $this
     */
    public function setItemId($itemId)
    {
        return $this->setData(self::ITEM_ID, $itemId);
    }

    /**
     * Get estimated ship date
     *
     * @return string
     */
    public function getEstimatedShipDate()
    {
        return $this->getData(self::ESTIMATED_SHIP_DATE);
    }

    /**
     * Set estimated ship date
     *
     * @param string $date
     * @return $this
     */
    public function setEstimatedShipDate($date)
    {
        return $this->setData(self::ESTIMATED_SHIP_DATE, $date);
    }
}
