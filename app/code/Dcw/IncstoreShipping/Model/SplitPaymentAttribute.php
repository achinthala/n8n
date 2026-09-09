<?php
namespace Dcw\IncstoreShipping\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Api\AbstractSimpleObject;

class SplitPaymentAttribute extends AbstractSimpleObject implements \Dcw\IncstoreShipping\Api\Data\SplitPaymentAttributeInterface
{
	
	/**
     * Get ResponseCode
     *
     * @return string|null
     */
    public function getResponseCode()
    {
       
        return $this->_get(self::RESPONCE_CODE);
    }
	
	/**
     * Get AuthCode
     *
     * @return string|null
     */
    public function getAuthCode()
    {
       
        return $this->_get(self::AUTH_CODE);
    }
	
	/**
     * Get AvsResultCode
     *
     * @return string|null
     */
    public function getAvsResultCode()
    {
       
        return $this->_get(self::AVS_RESULT_CODE);
    }
	
	/**
     * Get CvvResultCode
     *
     * @return string|null
     */
    public function getCvvResultCode()
    {
       
        return $this->_get(self::CVV_RESULT_CODE);
    }
	
	/**
     * Get CavvResultCode
     *
     * @return string|null
     */
    public function getCavvResultCode()
    {
       
        return $this->_get(self::CAVV_RESULT_CODE);
    }
	
	/**
     * Get TransId
     *
     * @return string|null
     */
    public function getTransId()
    {
       
        return $this->_get(self::TRANS_ID);
    }

	
	/**
     * Get RefTransId
     *
     * @return string|null
     */
    public function getRefTransId()
    {
       
        return $this->_get(self::REF_TRANS_ID);
    }
	
	/**
     * Get TransHash
     *
     * @return string|null
     */
    public function getTransHash()
    {
       
        return $this->_get(self::TRANS_HASH);
    }
	
	/**
     * Get TestRequest
     *
     * @return string|null
     */
    public function getTestRequest()
    {
       
        return $this->_get(self::TEST_REQUEST);
    }
	
	/**
     * Get AccountNumber
     *
     * @return string|null
     */
    public function getAccountNumber()
    {
       
        return $this->_get(self::ACCOUNT_NUMBER);
    }
	
	/**
     * Get AccountType
     *
     * @return string|null
     */
    public function getAccountType()
    {
       
        return $this->_get(self::ACCOUNT_TYPE);
    }
	
	

    /**
     * Set ResponseCode
     *
     * @param string $value
     */
    public function setResponseCode($value)
    {
      
        return $this->setData(self::RESPONCE_CODE, $value);
    }
	
	/**
     * Set AuthCode
     *
     * @param string $value
     */
    public function setAuthCode($value)
    {
      
        return $this->setData(self::AUTH_CODE, $value);
    }
	
	/**
     * Set AvsResultCode
     *
     * @param string $value
     */
    public function setAvsResultCode($value)
    {
      
        return $this->setData(self::AVS_RESULT_CODE, $value);
    }
	
	/**
     * Set CvvResultCode
     *
     * @param string $value
     */
    public function setCvvResultCode($value)
    {
      
        return $this->setData(self::CVV_RESULT_CODE, $value);
    }
	
	/**
     * Set CavvResultCode
     *
     * @param string $value
     */
    public function setCavvResultCode($value)
    {
      
        return $this->setData(self::CAVV_RESULT_CODE, $value);
    }
	
	/**
     * Set TransId
     *
     * @param string $value
     */
    public function setTransId($value)
    {
      
        return $this->setData(self::TRANS_ID, $value);
    }
	
	/**
     * Set RefTransId
     *
     * @param string $value
     */
    public function setRefTransId($value)
    {
      
        return $this->setData(self::REF_TRANS_ID, $value);
    }
	
	/**
     * Set TransHash
     *
     * @param string $value
     */
    public function setTransHash($value)
    {
      
        return $this->setData(self::TRANS_HASH, $value);
    }
	
	/**
     * Set TestRequest
     *
     * @param string $value
     */
    public function setTestRequest($value)
    {
      
        return $this->setData(self::TEST_REQUEST, $value);
    }
	
	/**
     * Set AccountNumber
     *
     * @param string $value
     */
    public function setAccountNumber($value)
    {
      
        return $this->setData(self::ACCOUNT_NUMBER, $value);
    }
	
	/**
     * Set AccountType
     *
     * @param string $value
     */
    public function setAccountType($value)
    {
      
        return $this->setData(self::ACCOUNT_TYPE, $value);
    }
	
}
