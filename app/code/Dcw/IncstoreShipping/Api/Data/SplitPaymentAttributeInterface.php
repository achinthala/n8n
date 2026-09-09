<?php
namespace Dcw\IncstoreShipping\Api\Data;

/**
 * Interface for saving the checkout comment to the quote for orders of logged in users
 * @api
 */
interface SplitPaymentAttributeInterface
{
    /**
     * #@+
     * Constants for keys of data array.
     * Identical to the name of the getter in snake case
     */
    
    /**
     * Incstore Item Shipping
     */
    const RESPONCE_CODE= 'response_code';
    const AUTH_CODE= 'auth_code';
    const AVS_RESULT_CODE= 'avs_result_code';
    const CVV_RESULT_CODE= 'cvv_result_code';
    const CAVV_RESULT_CODE= 'cavv_result_code';
    const TRANS_ID= 'trans_id';
    const REF_TRANS_ID= 'ref_trans_id';
    const TRANS_HASH= 'trans_hash';
    const TEST_REQUEST= 'test_request';
    const ACCOUNT_NUMBER= 'account_number';
    const ACCOUNT_TYPE= 'account_type';
	
	
     /**
     * Get Responce Code
     *
     * @return string|null
     */
    public function getResponseCode();

    /**
     * Get Auth Code
     *
     * @return string|null
     */
    public function getAuthCode();
    /**
     * Get Responce Code
     *
     * @return string|null
     */
    public function getAvsResultCode();
        /**
     * Get Responce Code
     *
     * @return string|null
     */
    public function getCvvResultCode();
    /**
     * Get Responce Code
     *
     * @return string|null
     */
    public function getCavvResultCode();
    /**
     * Get Responce Code
     *
     * @return string|null
     */
    public function getTransId();
    /**
     * Get Responce Code
     *
     * @return string|null
     */
    public function getRefTransId();
    /**
     * Get Responce Code
     *
     * @return string|null
     */
    public function getTransHash();
    /**
     * Get Responce Code
     *
     * @return string|null
     */
    public function getTestRequest();
    /**
     * Get Responce Code
     *
     * @return string|null
     */
    public function getAccountNumber();
    /**
     * Get Responce Code
     *
     * @return string|null
     */
    public function getAccountType();
    

    /**
     * Set Response Code.
     *
     * @param string $responseCode
     * @return $this
     */
    public function setResponseCode($responseCode);

    /** 
    * Set Auth Code
    *
    * @param string $authCode
    * @return $this
    */
    public function setAuthCode($authCode);
    /** 
    * Set Avs Result Code 
    *
    * @param string $avsResultCode
    * @return $this
    */
   public function setAvsResultCode($avsResultCode);
    /** 
    * Set Avs Result Code 
    *
    * @param string $cvvResultCode
    * @return $this
    */
	public function setCvvResultCode($cvvResultCode);
    /** 
    * Set Avs Result Code 
    *
    * @param string $cavvResultCode
    * @return $this
    */
	public function setCavvResultCode($cavvResultCode);
    /** 
    *  Set Trans Id
    *
    * @param string $transId
    * @return $this
    */
	public function setTransId($transId);
   /** 
    *  Set Ref Trans Id 
    *
    * @param string $refTransId
    * @return $this
    */
	public function setRefTransId($refTransId);
    /** 
    *  Set Trans Hash 
    *
    * @param string $transHash
    * @return $this
    */
	public function setTransHash($transHash);
    /** 
    * Set Test Request 
    *
    * @param string $testRequest
    * @return $this
    */
	public function setTestRequest($testRequest);
    /** 
    * Set AccountNumber 
    *
    * @param string $accountNumber
    * @return $this
    */
	public function setAccountNumber($accountNumber);
    /** 
    * Set Account Type
    *
    * @param string $accountType
    * @return $this
    */
	public function setAccountType($accountType);

}
