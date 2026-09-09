<?php
namespace Dcw\IncstoreShipping\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Api\AbstractSimpleObject;

class OrderItemAttribute extends AbstractSimpleObject implements \Dcw\IncstoreShipping\Api\Data\OrderItemAttributeInterface
{
	  /**
     * Get getShippingMetadata
     *
     * @return string
     */
    public function getShippingMetadata()
    {
       
        return $this->_get(self::SHIPPING_METADATA);
    }

     /**
     * Get getPdpLineItemData
     *
     * @return string
     */
    public function getPdpLineItemData()
    {
       
        return $this->_get(self::PDP_LINEITEMDATA);
    }

    /**
     * Set ShippingMetadata
     *
     * @param string $value
     */
    public function setShippingMetadata($value)
    {
      
        return $this->setData(self::SHIPPING_METADATA, $value);
    }

    /**
     * Set PdpLineItemData
     *
     * @param string $value
     */
    public function setPdpLineItemData($value)
    {
      
        return $this->setData(self::PDP_LINEITEMDATA, $value);
    }
	
}
