<?php
namespace Dcw\LineItemUpdateESD\Api\Data;

/**
 * Interface UpdateItemResponseInterface
 *
 * Represents API response structure for updating order item shipping estimate
 */
interface UpdateItemResponseInterface
{
    /**
     * Status key
     */
    public const STATUS = 'status';

    /**
     * Message key
     */
    public const MESSAGE = 'message';

    /**
     * Item ID key
     */
    public const ITEM_ID = 'item_id';

    /**
     * Estimated ship date key
     */
    public const ESTIMATED_SHIP_DATE = 'estimated_ship_date';

    /**
     * Get status
     *
     * @return bool
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param bool $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage();

    /**
     * Set message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * Get order item ID
     *
     * @return int
     */
    public function getItemId();

    /**
     * Set order item ID
     *
     * @param int $itemId
     * @return $this
     */
    public function setItemId($itemId);

    /**
     * Get estimated ship date
     *
     * @return string
     */
    public function getEstimatedShipDate();

    /**
     * Set estimated ship date
     *
     * @param string $date
     * @return $this
     */
    public function setEstimatedShipDate($date);
}
