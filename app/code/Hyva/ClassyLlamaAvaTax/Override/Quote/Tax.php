<?php

/**
 * Avalara_AvaTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright  Copyright (c) 2016 Avalara, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Hyva\ClassyLlamaAvaTax\Override\Quote;

use Avalara\AvaTax\Framework\Interaction\Tax\Get as InteractionGet;
use Avalara\AvaTax\Framework\Interaction\TaxCalculation as TaxCalculation;
use Avalara\AvaTax\Helper\Config;
use Avalara\AvaTax\Model\Tax\Sales\Total\Quote\Tax\Customs as CustomsTax;
use Magento\Customer\Api\Data\AddressInterfaceFactory as CustomerAddressFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory as CustomerAddressRegionFactory;
use Magento\Framework\Exception\RemoteServiceUnavailableException;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Framework\App\ResourceConnection;

class Tax extends \Avalara\AvaTax\Model\Tax\Sales\Total\Quote\Tax
{
    /**
     * Gift wrapping tax class
     *
     * Copied from \Magento\GiftWrapping\Model\Total\Quote\Tax\Giftwrapping it is an Enterprise-only module
     */
    const ITEM_TYPE = 'item_gw';
    const QUOTE_TYPE = 'quote_gw';
    const PRINTED_CARD_TYPE = 'printed_card_gw';

    /**
     * Retail Delivery Fee type
     */
    const ITEM_TYPE_RETAIL_DELIVERY = 'retail_delivery_fee';

    /**
     * @var InteractionGet
     */
    protected $interactionGetTax = null;

    /**
     * @var TaxCalculation
     */
    protected $taxCalculation = null;

    /**
     * @var Config
     */
    protected $config = null;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var \Magento\Tax\Api\Data\QuoteDetailsItemExtensionFactory
     */
    protected $extensionFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Avalara\AvaTax\Helper\TaxClass
     */
    protected $taxClassHelper;

    /**
     * @var CustomsTax
     */
    protected $customsTax;

    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $session;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * Registry key to track whether AvaTax GetTaxRequest was successful
     */
    const AVATAX_GET_TAX_REQUEST_ERROR = 'avatax_get_tax_request_error';

    const AVATAX_GET_TAX_REQUEST_ERROR_IDS = 'avatax_get_tax_request_error_ids';

    const MINIMUM_POST_CODE_LENGTH = 3;

    /**
     * Class constructor
     *
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Tax\Api\TaxCalculationInterface $taxCalculationService
     * @param QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory
     * @param QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory
     * @param TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory
     * @param CustomerAddressFactory $customerAddressFactory
     * @param CustomerAddressRegionFactory $customerAddressRegionFactory
     * @param \Magento\Tax\Helper\Data $taxData
     * @param InteractionGet $interactionGetTax
     * @param TaxCalculation $taxCalculation
     * @param Config $config
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemExtensionFactory $extensionFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Avalara\AvaTax\Helper\TaxClass $taxClassHelper
     * @param CustomsTax $customsTax
     * @param \Magento\Framework\Session\Generic $session
     */
    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculationService,
        QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory,
        QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory,
        TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory,
        CustomerAddressFactory $customerAddressFactory,
        CustomerAddressRegionFactory $customerAddressRegionFactory,
        \Magento\Tax\Helper\Data $taxData,
        InteractionGet $interactionGetTax,
        TaxCalculation $taxCalculation,
        Config $config,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Tax\Api\Data\QuoteDetailsItemExtensionFactory $extensionFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Registry $coreRegistry,
        \Avalara\AvaTax\Helper\TaxClass $taxClassHelper,
        CustomsTax $customsTax,
        \Magento\Framework\Session\Generic $session,
        ResourceConnection $resource
    ) {
        $this->interactionGetTax = $interactionGetTax;
        $this->taxCalculation = $taxCalculation;
        $this->config = $config;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->extensionFactory = $extensionFactory;
        $this->messageManager = $messageManager;
        $this->coreRegistry = $coreRegistry;
        $this->taxClassHelper = $taxClassHelper;
        $this->customsTax = $customsTax;
        $this->session = $session;
        $this->resource = $resource;

        parent::__construct(
            $taxConfig,
            $taxCalculationService,
            $quoteDetailsDataObjectFactory,
            $quoteDetailsItemDataObjectFactory,
            $taxClassKeyDataObjectFactory,
            $customerAddressFactory,
            $customerAddressRegionFactory,
            $taxData,
            $interactionGetTax,
            $taxCalculation,
            $config,
            $dataObjectHelper,
            $extensionFactory,
            $messageManager,
            $coreRegistry,
            $taxClassHelper,
            $customsTax,
            $session
        );
    }

    /**
     * Generate \Magento\Tax\Model\Sales\Quote\QuoteDetails object based on shipping assignment
     *
     * Base closely on this method, with the exception of the tax calculation call to calculate taxes:
     * @see \Magento\Tax\Model\Sales\Total\Quote\Tax::getQuoteTaxDetails()
     *
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Address\Total $total
     * @param bool $useBaseCurrency
     * @param string $storeId
     * @return \Magento\Tax\Api\Data\QuoteDetailsInterface
     */
    protected function getTaxQuoteDetails($shippingAssignment, $total, $storeId, $useBaseCurrency)
    {
        // If quote is virtual, getShipping will return billing address, so no need to check if quote is virtual
        $address = $shippingAssignment->getShipping()->getAddress();
        //Setup taxable items
        $priceIncludesTax = $this->_config->priceIncludesTax($address->getQuote()->getStore());
        $itemDataObjects = $this->mapItems($shippingAssignment, $priceIncludesTax, $useBaseCurrency);

        //Add shipping
        $shippingDataObject = $this->getShippingDataObject($shippingAssignment, $total, $useBaseCurrency);
        if ($shippingDataObject != null) {
            $this->addInfoToQuoteDetailsItemForShipping($shippingDataObject, $storeId);
            $itemDataObjects[] = $shippingDataObject;
        }


        $deliveryFeeDataObject = $this->getDeliveryFeeDataObject($shippingAssignment);
        if ($deliveryFeeDataObject != null) {
            $this->addInfoToQuoteDetailsItemForRetailFee($deliveryFeeDataObject);
            $itemDataObjects[] = $deliveryFeeDataObject;
        }


        //Start Add Expedite fees
        $expediteFeesDataObject = $this->getExpediteFeesObject($shippingAssignment, $total, $useBaseCurrency);

        if ($expediteFeesDataObject != null) {
            $this->addInfoToQuoteDetailsItemForExpediteFees($expediteFeesDataObject, $storeId);
            $itemDataObjects[] = $expediteFeesDataObject;
        }
        //End Add Expedite fees
        

        //process extra taxable items associated only with quote
        $quoteExtraTaxables = $this->mapQuoteExtraTaxables(
            $this->quoteDetailsItemDataObjectFactory,
            $address,
            $useBaseCurrency
        );
        if (!empty($quoteExtraTaxables)) {
            $itemDataObjects = array_merge($itemDataObjects, $quoteExtraTaxables);
        }

        //Preparation for calling taxCalculationService
        $quoteDetails = $this->prepareQuoteDetails($shippingAssignment, $itemDataObjects);

        return $quoteDetails;
    }

    /**
     * Add extension attribute fields to the \Magento\Tax\Model\Sales\Quote\ItemDetails object for the shipping record
     *
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterface $shippingDataObject
     * @param $storeId
     * @return $this
     */
    protected function addInfoToQuoteDetailsItemForExpediteFees(
        \Magento\Tax\Api\Data\QuoteDetailsItemInterface $shippingDataObject,
        $storeId
    ) {
        $this->addExtensionAttributesToTaxQuoteDetailsItem(
            $shippingDataObject,
            'OF030000',
            'OF030000',
            'Expedite Fees'
        );

        return $this;
    }

    protected function getExpediteFeesObject(
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total,
        $useBaseCurrency
    ) {
        $store = $shippingAssignment->getShipping()->getAddress()->getQuote()->getStore();

        $quoteId = $shippingAssignment->getShipping()->getAddress()->getQuote()->getId();

        $amastyQuoteTable = $this->resource->getTableName('amasty_extrafee_quote');
        $connection = $this->resource->getConnection();

        $amastyFeeQuoteLoad =  $connection->select()->from($amastyQuoteTable, 'fee_amount' )->where('quote_id = ?', $quoteId);
        $amastyFeeQuoteResult = $connection->fetchAll($amastyFeeQuoteLoad);
        $totalAmastyExtraFee = 0;
        
        if ($total->getShippingTaxCalculationAmount() !== null && count($amastyFeeQuoteResult) > 0) {

            foreach($amastyFeeQuoteResult as $amastyFee) {
                $totalAmastyExtraFee+= $amastyFee['fee_amount'];
            }

            /** @var QuoteDetailsItemInterface $itemDataObject */
            $itemDataObject = $this->quoteDetailsItemDataObjectFactory->create()
                ->setType('Expedite_Fees')
                ->setCode('Expedite_Fees')
                ->setQuantity(1);
            if ($useBaseCurrency) {
                $itemDataObject->setUnitPrice($totalAmastyExtraFee);
            } else {
                $itemDataObject->setUnitPrice($totalAmastyExtraFee);
            }
            if ($total->getShippingDiscountAmount()) {
                if ($useBaseCurrency) {
                    $itemDataObject->setDiscountAmount(0);
                } else {
                    $itemDataObject->setDiscountAmount(0);
                }
            }
            $itemDataObject->setTaxClassKey(
                $this->taxClassKeyDataObjectFactory->create()
                    ->setType(\Magento\Tax\Api\Data\TaxClassKeyInterface::TYPE_ID)
                    ->setValue($this->_config->getShippingTaxClass($store))
            );
            $itemDataObject->setIsTaxIncluded(
                $this->_config->shippingPriceIncludesTax($store)
            );
            return $itemDataObject;
        }

        return null;
    }
}
