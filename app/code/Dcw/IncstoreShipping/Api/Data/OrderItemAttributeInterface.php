<?php
namespace Dcw\IncstoreShipping\Api\Data;

/**
 * Interface for saving the checkout comment to the quote for orders of logged in users
 * @api
 */
interface OrderItemAttributeInterface
{
    /**
     * #@+
     * Constants for keys of data array.
     * Identical to the name of the getter in snake case
     */
    
    /**
     * Incstore Item Shipping
     */
    const SHIPPING_METADATA = 'shipping_metadata';
    const PDP_LINEITEMDATA = 'pdp_line_item';
	
	
    /**
     * Get Incstore Item Shipping
     *
     * @return string
     */
    public function getShippingMetadata();

      /**
     * Get Incstore Item Shipping
     *
     * @return string
     */
    public function getPdpLineItemData();

    /**
     * Set Incstore Item Shipping
     *
     * @param string $value
     * @return $this
     */
    public function setShippingMetadata($value);

    /**
     * Set Incstore Item Shipping
     *
     * @param string $value
     * @return $this
     */
    public function setPdpLineItemData($value);

}
