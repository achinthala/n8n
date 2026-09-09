<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Request a Quote Base for Magento 2
 */

namespace Dcw\SaveShippingAmount\Controller\Move;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Context;

use Amasty\RequestQuote\Model\Quote\Frontend\GetAmastyQuote;
use Amasty\RequestQuote\Model\Quote\Session as RequestSession;
use Amasty\RequestQuote\Model\QuoteRepository;
use Amasty\RequestQuote\Model\UrlResolver;
use Amasty\RequestQuote\Helper\Data as ConfigHelper;
use Amasty\RequestQuote\Helper\Date as DateHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Dcw\RequestQuote\ViewModel\ActiveCartIcon;
use Magento\Quote\Model\Quote as NativeQuote;
use Amasty\RequestQuote\Model\Quote;
use Amasty\RequestQuote\Model\QuoteFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Quote\Model\Quote\Address\RateFactory;


class InQuote extends \Amasty\RequestQuote\Controller\Move\AbstractMove
{
    /**
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */

     /**
     * @var null|Quote
     */
    private $quote = null;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    private $magentoQuoteRepository;

    /**
     * @var RequestRepository
     */
    protected $requestRepository;

    /**
     * @var UrlResolver
     */
    protected $urlResolver;

    protected $resource;
    /**
     * @var array
     */
    private $sessions = [];

    /**
     * @var GetAmastyQuote
     */
    private $getAmastyQuote;

    private $rateFactory;

    protected $customerSession;

    protected $customerRepository;

    protected $addressRepository;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var DateHelper
     */
    protected $dateHelper;

    public function __construct(
        QuoteFactory $quoteFactory,
        QuoteRepository $quoteRepository,
        \Magento\Quote\Model\QuoteRepository $magentoQuoteRepository,
        CheckoutSession $checkoutSession,
        RequestSession $requestSession,
        Context $context,
        UrlResolver $urlResolver,
        GetAmastyQuote $getAmastyQuote,
        ResourceConnection $resource,
        RateFactory $rateFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        ConfigHelper $configHelper,
        DateHelper $dateHelper
    )  {
        $this->resource = $resource;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository; // CRITICAL: Assign quoteRepository to property
        $this->magentoQuoteRepository = $magentoQuoteRepository;
        $this->rateFactory = $rateFactory;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->configHelper = $configHelper;
        $this->dateHelper = $dateHelper;
        parent::__construct(
            $quoteFactory,
            $quoteRepository,
            $magentoQuoteRepository,
            $checkoutSession,
            $requestSession,
            $quoteRepository,
            $context,
            $urlResolver,
            $getAmastyQuote
        );
    }
   
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $magentoCartQuote = $this->getSession('checkout')->getQuote();
        $quoteAddressShippingAmount  = $magentoCartQuote->getShippingAddress()->getShippingChargeApi();
        $quoteAddressTaxAmount = $magentoCartQuote->getShippingAddress()->getBaseTaxAmount();
        
        // Get the Amasty quote (quote cart)
        $amastyQuote = $this->getQuote();
        $amastyQuoteHasItems = $amastyQuote && $amastyQuote->getId() && count($amastyQuote->getAllVisibleItems()) > 0;

        if ($magentoCartQuote->getId()) {
            // Combine items: Copy Magento cart items to Amasty quote cart (without removing existing quote cart items)
            $this->combineQuotes($amastyQuote, $magentoCartQuote);

            //:::::::::::Start save amasty quote id to quote_address:::::::::::
            $amasty_quoteId = $amastyQuote->getId();

            $amastyPriceUpdate = $this->getAmastyQuoteByQuoteId($amasty_quoteId, $quoteAddressShippingAmount, $quoteAddressTaxAmount);

            //:::::::::::End :::::::::::::::::::::::::::::::::::::::::::::::::
            // Show quote cart icon - "show cart that has latest update" (Checkout Later)
            $this->checkoutSession->setData(ActiveCartIcon::getSessionKey(), ActiveCartIcon::getTypeQuote());
            $result->setUrl($this->urlResolver->getCartUrl());
        } elseif ($amastyQuoteHasItems) {
            // Magento cart empty/no ID but Amasty quote has items - allow Checkout Later to proceed
            // (e.g. user has only quote cart items, or session/quote loading edge case)
            $this->checkoutSession->setData(ActiveCartIcon::getSessionKey(), ActiveCartIcon::getTypeQuote());
            $result->setUrl($this->urlResolver->getCartUrl());
        } else {
            $this->messageManager->addErrorMessage(
                __('This is approved quote.')
            );
            $result->setUrl($this->_url->getUrl('checkout/cart'));
        }

        return $result;
    }

    /**
     * Combine Magento cart items into Amasty quote cart
     * This preserves existing quote cart items and adds Magento cart items
     *
     * @param Quote $amastyQuote The Amasty quote (quote cart)
     * @param \Magento\Quote\Model\Quote $magentoCartQuote The Magento cart quote
     * @return void
     */
    private function combineQuotes(Quote $amastyQuote, \Magento\Quote\Model\Quote $magentoCartQuote): void
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $logger = $objectManager->get(\Psr\Log\LoggerInterface::class);
        
        $logger->info('InQuote: Combining quotes', [
            'amasty_quote_id' => $amastyQuote->getId(),
            'amasty_quote_items_count' => count($amastyQuote->getAllItems()),
            'magento_cart_quote_id' => $magentoCartQuote->getId(),
            'magento_cart_items_count' => count($magentoCartQuote->getAllItems())
        ]);
        
        // Reload quotes to ensure fresh data
        // Only reload if quote IDs exist
        if ($amastyQuote->getId()) {
            try {
                $amastyQuote = $this->quoteRepository->get($amastyQuote->getId());
                $amastyQuote->getItemsCollection()->load();
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // Quote doesn't exist, log and continue with existing quote object
                $logger->warning('InQuote: Amasty quote not found in repository', [
                    'quote_id' => $amastyQuote->getId()
                ]);
            }
        }
        
        if ($magentoCartQuote->getId()) {
            try {
                $magentoCartQuote = $this->magentoQuoteRepository->get($magentoCartQuote->getId());
                $magentoCartQuote->getItemsCollection()->load();
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // Quote doesn't exist, log and continue with existing quote object
                $logger->warning('InQuote: Magento cart quote not found in repository', [
                    'quote_id' => $magentoCartQuote->getId()
                ]);
            }
        }
        
        // Track items for logging
        $mergedItems = [];
        $mergedFromCart = [];
        
        // Copy items from Magento cart to Amasty quote (preserving existing quote cart items)
        foreach ($magentoCartQuote->getAllVisibleItems() as $magentoItem) {
            // Get cart item data for logging
            $cartItemData = [
                'sku' => $magentoItem->getSku(),
                'name' => $magentoItem->getName(),
                'qty' => $magentoItem->getQty(),
                'price' => $magentoItem->getPrice(),
                'product_id' => $magentoItem->getProductId(),
            ];
            
            // Check if item already exists in quote cart (by comparing SKU, product ID, and all options including custom options)
            $itemExists = false;
            $oldQty = 0;
            foreach ($amastyQuote->getAllItems() as $existingItem) {
                if ($existingItem->getParentItemId()) {
                    continue;
                }
                // Same product
                if ($existingItem->getSku() != $magentoItem->getSku() ||
                    $existingItem->getProductId() != $magentoItem->getProductId()) {
                    continue;
                }
                // Compare all options (configurable attributes, custom options, etc.) so different Custom Length = different line
                if ($this->quoteItemOptionsMatch($existingItem, $magentoItem)) {
                    // Item exists, merge quantities
                    $oldQty = $existingItem->getQty();
                    $existingItem->setQty($existingItem->getQty() + $magentoItem->getQty());
                    $itemExists = true;

                    // Track merged item
                    $mergedItems[] = [
                        'item_id' => $existingItem->getId(),
                        'sku' => $existingItem->getSku(),
                        'name' => $existingItem->getName(),
                        'old_qty' => $oldQty,
                        'new_qty' => $existingItem->getQty(),
                        'merged_qty' => $magentoItem->getQty(),
                    ];
                    $mergedFromCart[] = $cartItemData;
                    break;
                }
            }

            if (!$itemExists) {
                // Clone the item and add to Amasty quote
                $newItem = clone $magentoItem;
                $newItem->setId(null); // Clear ID so it becomes a new item
                $newItem->setItemId(null);
                $newItem->setQuoteId($amastyQuote->getId());
                $newItem->setQuote($amastyQuote);
                $newItem->setIsObjectNew(true);
                $newItem->setParentItemId(null);
                
                // Add to Amasty quote
                $amastyQuote->addItem($newItem);
                
                // Handle child items
                if ($magentoItem->getHasChildren()) {
                    foreach ($magentoItem->getChildren() as $child) {
                        $newChild = clone $child;
                        $newChild->setId(null);
                        $newChild->setItemId(null);
                        $newChild->setQuoteId($amastyQuote->getId());
                        $newChild->setQuote($amastyQuote);
                        $newChild->setIsObjectNew(true);
                        $newChild->setParentItemId(null);
                        $newChild->setParentItem($newItem);
                        $amastyQuote->addItem($newChild);
                    }
                }
                
                // Track new item added
                $mergedItems[] = [
                    'sku' => $newItem->getSku(),
                    'name' => $newItem->getName(),
                    'qty' => $newItem->getQty(),
                    'price' => $newItem->getPrice(),
                    'product_id' => $newItem->getProductId(),
                    'is_new' => true,
                ];
                $mergedFromCart[] = $cartItemData;
            }
        }
        
        // Set store ID and collect totals
        $amastyQuote->setStoreId($magentoCartQuote->getStoreId());
        $amastyQuote->setTotalsCollectedFlag(false);
        $amastyQuote->collectTotals();
        
        // Set expiration and reminder dates (similar to Approve controller)
        if ($expDays = $this->configHelper->getExpirationTime()) {
            $amastyQuote->setExpiredDate($this->dateHelper->increaseDays($expDays));
        }
        if ($remDays = $this->configHelper->getReminderTime()) {
            $amastyQuote->setReminderDate($this->dateHelper->increaseDays($remDays));
        }
        
        // Save the Amasty quote with combined items
        $this->quoteRepository->save($amastyQuote);

        // Log items merged (only if items were actually merged)
        if (!empty($mergedItems) && $amastyQuote->getId()) {
            try {
                $changeLogger = $objectManager->get(\Dcw\RequestQuote\Service\QuoteChangeLogger::class);
                $changeLogger->logItemsMerged((int)$amastyQuote->getId(), $mergedItems, $mergedFromCart);
            } catch (\Exception $e) {
                // Silent fail - don't break quote operations if logging fails
                $logger->warning('InQuote: Failed to log items merged', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Clear Magento cart after moving items
        // Use removeAllItems() which is more efficient and ensures all items are removed
        $magentoCartQuote->removeAllItems();
        $magentoCartQuote->setTotalsCollectedFlag(false);
        $magentoCartQuote->collectTotals();
        $this->magentoQuoteRepository->save($magentoCartQuote);
        
        // CRITICAL: Clear checkout session completely to reflect empty cart
        // Setting quote ID to null forces getQuote() to return a new empty quote
        // This ensures the mini-cart shows the cart is empty
        try {
            $reflection = new \ReflectionClass($this->checkoutSession);
            if ($reflection->hasProperty('_quote')) {
                $property = $reflection->getProperty('_quote');
                $property->setAccessible(true);
                $property->setValue($this->checkoutSession, null);
            }
        } catch (\Exception $e) {
            // If reflection fails, continue
        }
        
        // Set quote ID to null to clear the cart from session
        // This will make getQuote() return a new empty quote on next access
        $this->checkoutSession->setQuoteId(null);
        $this->checkoutSession->setCartWasUpdated(true);
        
        // Also clear any cached quote data
        $this->checkoutSession->clearQuote();
        
        // Update Amasty quote session
        $this->getSession('request')->setQuoteId($amastyQuote->getId());
        
        // Reload quote in session to ensure it's properly cached
        $this->getSession('request')->getQuote();
        
        $logger->info('InQuote: Quotes combined successfully', [
            'amasty_quote_id' => $amastyQuote->getId(),
            'final_items_count' => count($amastyQuote->getAllItems()),
            'visible_items_count' => count($amastyQuote->getAllVisibleItems())
        ]);
    }

    /**
     * Compare two quote items by all options (configurable attributes, custom options, etc.).
     * Returns true only if options match so that e.g. different Custom Length = different line items.
     *
     * @param \Magento\Quote\Model\Quote\Item|Quote\Item $itemA
     * @param \Magento\Quote\Model\Quote\Item|Quote\Item $itemB
     * @return bool
     */
    private function quoteItemOptionsMatch($itemA, $itemB): bool
    {
        $optsA = $this->getQuoteItemOptionsSignature($itemA);
        $optsB = $this->getQuoteItemOptionsSignature($itemB);
        return $optsA === $optsB;
    }

    /**
     * Build a signature string from item options (code => value) for comparison.
     * Excludes options that are not part of product identity (e.g. amasty_quote_id).
     *
     * @param \Magento\Quote\Model\Quote\Item|Quote\Item $item
     * @return string
     */
    private function getQuoteItemOptionsSignature($item): string
    {
        $parts = [];
        $exclude = ['amasty_quote_id', 'amasty_quote_price', 'info_buyRequest'];
        foreach ($item->getOptions() ?: [] as $option) {
            $code = $option->getCode();
            if (in_array($code, $exclude, true)) {
                continue;
            }
            $parts[] = $code . '=' . (string)$option->getValue();
        }
        sort($parts);
        return implode('|', $parts);
    }

    /**
     * @inheritdoc
     */
    protected function getType()
    {
        return 'request';
    }

    public function getAmastyQuoteByQuoteId($quoteId, $shippingAmount,$getAmastyQuoteByQuoteId)
    {   
        // Get the table name
        $amastyQuoteTable = $this->resource->getTableName('amasty_quote');
        $connection = $this->resource->getConnection();
        
        // Check if the quote exists with the provided quote_id
        $sqlSelect = "SELECT * FROM $amastyQuoteTable WHERE quote_id = :quoteId LIMIT 1";
        $bind = ['quoteId' => $quoteId];
        
        // Fetch the record
        $result = $connection->fetchRow($sqlSelect, $bind);
        
        $orderRestrictionReset = $this->checkoutSession->getOrderRestrictionReset();

        // If the record is found, update the shipping_amount
        if ($result && !isset($orderRestrictionReset) || $orderRestrictionReset != '1') {
            $currentCustomerId = $this->customerSession->getCustomerId();
            if($shippingAmount && $currentCustomerId){
                $customer = $this->customerRepository->getById($currentCustomerId);
                $defaultShippingAddress = $customer->getDefaultShipping(); 
                if (isset($defaultShippingAddress) && $defaultShippingAddress !== '') {
                    $sqlUpdate = "UPDATE $amastyQuoteTable SET shipping_amount = :shippingAmount, tax_amount = :tax_amount, custom_fee= :custom_fee, shipping_configured= :shipping_configured WHERE quote_id = :quoteId";
                    $bindUpdate = ['shippingAmount' => $shippingAmount,'tax_amount'=>$getAmastyQuoteByQuoteId, 'custom_fee' => $shippingAmount, 'shipping_configured'=>1, 'quoteId' => $quoteId];
                }else{
                    $sqlUpdate = "UPDATE $amastyQuoteTable SET tax_amount = :tax_amount WHERE quote_id = :quoteId";
                    $bindUpdate = ['tax_amount'=>$getAmastyQuoteByQuoteId, 'quoteId' => $quoteId];
                }
            }else{
                $sqlUpdate = "UPDATE $amastyQuoteTable SET tax_amount = :tax_amount WHERE quote_id = :quoteId";
                $bindUpdate = ['tax_amount'=>$getAmastyQuoteByQuoteId, 'quoteId' => $quoteId];
            }
            // Execute the update query
            $connection->query($sqlUpdate, $bindUpdate);

            // Load quote model and update using repository
            try {
                $quote = $this->magentoQuoteRepository->get($quoteId); // Load quote by ID
                $shippingAddress = $quote->getShippingAddress();

                if($shippingAmount && $currentCustomerId){
                    
                    if (isset($defaultShippingAddress) && $defaultShippingAddress !== '') {
                       
                        //create shipping rate
                        $rate = $this->rateFactory->create();
                        $rate->setCode('amasty_quote_custom_fee_amasty_quote_custom_fee');
                        $rate->setCarrier('amasty_quote_custom_fee');
                        $rate->setCarrierTitle('Custom');
                        $rate->setMethod('amasty_quote_custom_fee');
                        $rate->setMethodTitle('Custom Fee');
                        $rate->setPrice($shippingAmount);
                        $rate->setCost($shippingAmount);

                        $shippingAddress->addShippingRate($rate);
                        
                        $shippingAddress->setShippingMethod('amasty_quote_custom_fee_amasty_quote_custom_fee');

                        $this->setDefaultAddressToQuoteShippingAddress($quote);
                        $this->magentoQuoteRepository->save($quote); // Save the changes
                    }
                }else{
                    return false;
                }
               

                // Recalculate totals
                $quote->collectTotals();

                // Save the updated quote
                $this->magentoQuoteRepository->save($quote);
                
                return true;  // Return true if updates are successful
            } catch (\Exception $e) {
                // Handle the exception if quote is not found or cannot be saved
                return false;
               
            }
        }else if(isset($orderRestrictionReset) && $orderRestrictionReset ==1){ //set only tax
            $sqlUpdate = "UPDATE $amastyQuoteTable SET tax_amount = :tax_amount WHERE quote_id = :quoteId";
            $bindUpdate = ['tax_amount'=>$getAmastyQuoteByQuoteId, 'quoteId' => $quoteId];
            // Execute the update query
            $connection->query($sqlUpdate, $bindUpdate);

            // Load quote model and update using repository
            try {
                $quote = $this->magentoQuoteRepository->get($quoteId); // Load quote by ID
                $shippingAddress = $quote->getShippingAddress();
                
                //create shipping rate
                $rate = $this->rateFactory->create();
                $rate->setCode('amasty_quote_custom_fee_amasty_quote_custom_fee');
                $rate->setCarrier('amasty_quote_custom_fee');
                $rate->setCarrierTitle('Custom');
                $rate->setMethod('amasty_quote_custom_fee');
                $rate->setMethodTitle('Custom Fee');
                $rate->setPrice(0);
                $rate->setCost(0);
                $shippingAddress->addShippingRate($rate);
                
                $shippingAddress->setShippingMethod('amasty_quote_custom_fee_amasty_quote_custom_fee');
                
                $quote->setTotalsCollectedFlag(false);
                $quote->collectTotals(); 
                $this->magentoQuoteRepository->save($quote);
                $this->setDefaultAddressToQuoteShippingAddress($quote);
                 // Save the changes

                return true;  // Return true if updates are successful
            } catch (\Exception $e) {
                return false;
            }
        }

        // Return false if no record was found
        return false;
    }

    public function setDefaultAddressToQuoteShippingAddress($quote){
        $currentCustomerId = $this->customerSession->getCustomerId(); // Get the logged-in customer ID
        
        if ($currentCustomerId) {
            
                try {
                    // Load the current customer
                    $customer = $this->customerRepository->getById($currentCustomerId);
                    $defaultShippingAddress = $customer->getDefaultShipping(); // Get the default shipping address ID
                    $shippingAddress = $quote->getShippingAddress();
                    if ($defaultShippingAddress) {
                        // Load the customer's default shipping address
                        $address = $this->addressRepository->getById($defaultShippingAddress);
                       
                        // Set the shipping address to the quote
                    
                        $shippingAddress->setCustomerId($currentCustomerId);
                        $shippingAddress->setFirstname($address->getFirstname());
                        $shippingAddress->setLastname($address->getLastname());
                        $shippingAddress->setStreet($address->getStreet());
                        $shippingAddress->setCity($address->getCity());
                        $shippingAddress->setRegionId($address->getRegionId());
                        $shippingAddress->setPostcode($address->getPostcode());
                        $shippingAddress->setCountryId($address->getCountryId());
                        $shippingAddress->setTelephone($address->getTelephone());
                       
                    
                        // Optionally, set the billing address the same as the shipping address
                        $billingAddress = $quote->getBillingAddress();
                        $billingAddress->setCustomerId($currentCustomerId);
                        $billingAddress->setFirstname($address->getFirstname());
                        $billingAddress->setLastname($address->getLastname());
                        $billingAddress->setStreet($address->getStreet());
                        $billingAddress->setCity($address->getCity());
                        $billingAddress->setRegionId($address->getRegionId());
                        $billingAddress->setPostcode($address->getPostcode());
                        $billingAddress->setCountryId($address->getCountryId());
                        $billingAddress->setTelephone($address->getTelephone());
                        // Save the updated addresses
                        $shippingAddress->save();
                        $billingAddress->save();
                        // Save the quote
                        $quote->setTotalsCollectedFlag(false);
                        $quote->collectTotals(); 
                        $this->magentoQuoteRepository->save($quote); // Make sure you have injected the QuoteRepository
                        
                        return true;
                    }
                } catch (\Exception $e) {
                    return false;
                }
            
        }

        return true;
    }


    public function createLog($msg){
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/InQuote.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
}
