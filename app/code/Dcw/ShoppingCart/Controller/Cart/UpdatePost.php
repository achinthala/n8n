<?php

declare(strict_types=1);

namespace Dcw\ShoppingCart\Controller\Cart;

use Exception;
use Amasty\RequestQuote\Api\Data\QuoteItemInterface;
use Amasty\RequestQuote\Api\QuoteRepositoryInterface;
use Amasty\RequestQuote\Helper\Data;
use Amasty\RequestQuote\Model\RegistryConstants;
use Amasty\RequestQuote\Model\Source\Status;
use Magento\Customer\Api\AccountManagementInterface as CustomerAccountManagement;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\InputException;
use Amasty\RequestQuote\Model\Source\CustomerNotificationTemplates;
use Dcw\RequestQuote\Service\DiscountThresholdService;
use Dcw\RequestQuote\Service\QuoteChangeLogger;
use Dcw\RequestQuote\ViewModel\Data as RequestQuoteViewModel;
use Dcw\RequestQuote\Api\Data\QuoteChangeLogInterface;
use Magento\Framework\App\ResourceConnection;
use Amasty\RequestQuote\Model\Quote\Session as AmastyQuoteSession;
use Amasty\RequestQuote\Helper\Data as AmastyDataHelper;
use Amasty\RequestQuote\Helper\Date as AmastyDateHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Amasty\RequestQuote\Model\Cart as AmastyCart;
use Magento\Framework\Locale\ResolverInterface as LocaleResolver;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Json\EncoderInterface;
use Amasty\RequestQuote\Helper\Cart as AmastyCartHelper;
use Magento\Framework\DataObjectFactory;
use Amasty\RequestQuote\Model\Email\Sender;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Amasty\RequestQuote\Model\Email\AdminNotification;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Amasty\RequestQuote\Model\HidePrice\Provider as HidePriceProvider;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\CustomerExtractor;
use Amasty\RequestQuote\Model\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Amasty\RequestQuote\Model\UrlResolver;
use Magento\Framework\Filter\LocalizedToNormalized;
use Amasty\RequestQuote\Model\ConfigProvider;
use Amasty\RequestQuote\Api\AccountManagementInterface as QuoteAccountManagement;
use Dcw\RequestQuote\ViewModel\ActiveCartIcon;
use Dcw\SaveShippingAmount\Service\MoveToCartService;
use Psr\Log\LoggerInterface;

class UpdatePost extends \Hyva\AmastyRequestQuote\Controller\Cart\UpdatePost
{
    /**
     * @var MoveToCartService
     */
    private $moveToCartService;

    /**
     * @var RequestQuoteViewModel
     */
    protected $requestQuoteViewModel;

    /**
     * @var QuoteRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var AmastyQuoteSession
     */
    protected $amastyQuoteSession;

    /**
     * @var DiscountThresholdService
     */
    protected $discountThresholdService;

    /**
     * @var AmastyDataHelper
     */
    protected $amastyDataHelper;

    /**
     * @var AmastyDateHelper
     */
    protected $amastyDateHelper;

    /**
     * @var QuoteChangeLogger
     */
    protected $quoteChangeLogger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param RequestQuoteViewModel $requestQuoteViewModel
     * @param QuoteRepositoryInterface $quoteRepository
     * @param AmastyQuoteSession $amastyQuoteSession
     * @param DiscountThresholdService $discountThresholdService
     * @param AmastyDataHelper $amastyDataHelper
     * @param AmastyDateHelper $amastyDateHelper
     * @param QuoteChangeLogger $quoteChangeLogger
     * @param ScopeConfigInterface $scopeConfig
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param FormKeyValidator $formKeyValidator
     * @param AmastyCart $amastyCart
     * @param LocaleResolver $localeResolver
     * @param PageFactory $resultPageFactory
     * @param EncoderInterface $encoder
     * @param AmastyCartHelper $cartHelper
     * @param DataObjectFactory $dataObjectFactory
     * @param Sender $emailSender
     * @param SessionFactory $customerSessionFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param AmastyDataHelper $configHelper
     * @param AdminNotification $adminNotification
     * @param AccountManagementInterface $accountManagement
     * @param CustomerUrl $customerUrl
     * @param AuthenticationInterface $authentication
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param PhpCookieManager $cookieManager
     * @param HidePriceProvider $hidePriceProvider
     * @param TimezoneInterface $timezone
     * @param CustomerExtractor $customerExtractor
     * @param LoggerInterface $logger
     * @param Registry $registry
     * @param DateTime $dateTime
     * @param UrlResolver $urlResolver
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        RequestQuoteViewModel $requestQuoteViewModel,
        QuoteRepositoryInterface $quoteRepository,
        AmastyQuoteSession $amastyQuoteSession,
        DiscountThresholdService $discountThresholdService,
        AmastyDataHelper $amastyDataHelper,
        AmastyDateHelper $amastyDateHelper,
        QuoteChangeLogger $quoteChangeLogger,
        ScopeConfigInterface $scopeConfig,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        FormKeyValidator $formKeyValidator,
        AmastyCart $amastyCart,
        LocaleResolver $localeResolver,
        PageFactory $resultPageFactory,
        EncoderInterface $encoder,
        AmastyCartHelper $cartHelper,
        DataObjectFactory $dataObjectFactory,
        Sender $emailSender,
        SessionFactory $customerSessionFactory,
        PriceCurrencyInterface $priceCurrency,
        AmastyDataHelper $configHelper,
        AdminNotification $adminNotification,
        CustomerAccountManagement $accountManagement,
        CustomerUrl $customerUrl,
        AuthenticationInterface $authentication,
        CookieMetadataFactory $cookieMetadataFactory,
        PhpCookieManager $cookieManager,
        HidePriceProvider $hidePriceProvider,
        TimezoneInterface $timezone,
        CustomerExtractor $customerExtractor,
        LoggerInterface $logger,
        Registry $registry,
        DateTime $dateTime,
        UrlResolver $urlResolver,
        MoveToCartService $moveToCartService,
        ?LocalizedToNormalized $localizedToNormalized = null,
        ?ConfigProvider $configProvider = null,
        ?QuoteAccountManagement $quoteAccountManagement = null,
        array $data = []
    ) {
        $this->moveToCartService = $moveToCartService;
        $this->requestQuoteViewModel = $requestQuoteViewModel;
        $this->quoteRepository = $quoteRepository;
        $this->amastyQuoteSession = $amastyQuoteSession;
        $this->discountThresholdService = $discountThresholdService;
        $this->amastyDataHelper = $amastyDataHelper;
        $this->amastyDateHelper = $amastyDateHelper;
        $this->quoteChangeLogger = $quoteChangeLogger;
        $this->scopeConfig = $scopeConfig;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        parent::__construct(
            $context,
            $scopeConfig,
            $amastyQuoteSession,
            $storeManager,
            $formKeyValidator,
            $amastyCart,
            $localeResolver,
            $resultPageFactory,
            $encoder,
            $cartHelper,
            $dataObjectFactory,
            $emailSender,
            $customerSessionFactory,
            $priceCurrency,
            $configHelper,
            $adminNotification,
            $accountManagement,
            $customerUrl,
            $authentication,
            $cookieMetadataFactory,
            $cookieManager,
            $hidePriceProvider,
            $timezone,
            $customerExtractor,
            $logger,
            $registry,
            $dateTime,
            $urlResolver,
            $localizedToNormalized,
            $configProvider,
            $quoteAccountManagement
        );
    }

    /**
     * Get quote repository
     *
     * @return QuoteRepositoryInterface
     */
    private function getQuoteRepository(): QuoteRepositoryInterface
    {
        return $this->quoteRepository;
    }
    /**
     * @return void
     */
    protected function _emptyShoppingCart()
    {
        try {
            $this->cart->truncate()->save();
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (Exception $exception) {
            $this->messageManager->addExceptionMessage($exception, __('We can\'t update the shopping cart.'));
        }
    }


    protected function _updateShoppingCart()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $editQuoteId = $this->getRequest()->getParam('edit_quote_id') 
                        ?: $this->getRequest()->getParam('relation_parent_id')
                        ?: $this->getRequest()->getPostValue('edit_quote_id')
                        ?: $this->getRequest()->getPostValue('relation_parent_id');
            
            if (!$editQuoteId) {
                $editQuoteId = $this->getRequest()->getQuery('edit_quote_id');
            }
            
            if ($editQuoteId) {
                try {
                    $quote = $this->getQuoteRepository()->get((int)$editQuoteId);
                    $quote->getItemsCollection()->load();
                    
                    $this->amastyQuoteSession->setQuoteId((int)$editQuoteId);
                    
                    $checkoutSession = $this->getCheckoutSession();
                    $checkoutSession->setQuoteId($quote->getId());
                    
                    try {
                        $reflection = new \ReflectionClass($checkoutSession);
                        if ($reflection->hasProperty('_quote')) {
                            $property = $reflection->getProperty('_quote');
                            $property->setAccessible(true);
                            $property->setValue($checkoutSession, $quote);
                        }
                    } catch (\Exception $e) {
                        // Continue if reflection fails
                    }
                } catch (\Exception $e) {
                    $quote = $this->getCheckoutSession()->getQuote();
                }
            } else {
                $quote = $this->getCheckoutSession()->getQuote();
            }
            
            if (!$quote || !$quote->getId()) {
                throw new LocalizedException(__('Quote not found. Please try again.==='.$editQuoteId));
            }
            
            // Check if quote is locked by admin before allowing customer to modify
            // Check if we're editing an existing quote (either via edit_quote_id or relation_parent_id)
            $quoteIdToCheck = $editQuoteId ?: $quote->getRelationParentId();
            if ($quoteIdToCheck) {
                try {
                    if ($this->requestQuoteViewModel->isLockedByAdmin((int)$quoteIdToCheck)) {
                        $lockStatus = $this->requestQuoteViewModel->getLockStatus((int)$quoteIdToCheck);
                        $adminName = $lockStatus['locked_by_name'] ?? 'Admin';
                        throw new LocalizedException(
                            __('This quote is currently being edited by %1. Please try again later.', $adminName)
                        );
                    }
                } catch (LocalizedException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    // Continue if lock check fails (don't block quote updates)
                }
            }
            
            $remarks = $this->getRequest()->getParam('remarks', null);

            if ($remarks && trim($remarks)) {
                $quote->setRemarks($this->cartHelper->prepareCustomerNoteForSave($remarks));
            }

            $cartData = $this->getRequest()->getParam('cart');

            if (is_array($cartData)) {
                $originalQuoteData = null;
                $quoteId = (int)$quote->getId();
                $isAmastyQuote = false;
                $validationException = null;
                
                try {
                    $quoteRepository = $this->getQuoteRepository();
                    if (method_exists($quoteRepository, 'isAmastyQuote')) {
                        $isAmastyQuote = $quoteRepository->isAmastyQuote($quoteId);
                    } else {
                        try {
                            $quoteRepository->get($quoteId);
                            $isAmastyQuote = true;
                        } catch (\Exception $e) {
                            $isAmastyQuote = false;
                        }
                    }
                    
                    if ($isAmastyQuote) {
                        // Always load original quote snapshot for logging (qty/price changes),
                        // regardless of whether a discount exists.
                        try {
                            $originalQuote = $quoteRepository->get($quoteId);
                            $originalQuote->getItemsCollection()->load();
                            $originalQuoteData = [
                                'subtotal' => $originalQuote->getSubtotal(),
                                'grand_total' => $originalQuote->getGrandTotal(),
                                'items_count' => count($originalQuote->getAllItems()),
                                'items' => []
                            ];
                            foreach ($originalQuote->getAllItems() as $item) {
                                if ($item->getParentItemId()) {
                                    continue;
                                }
                                $originalQuoteData['items'][$item->getId()] = [
                                    'item_id'   => $item->getId(),
                                    'sku'       => $item->getSku(),
                                    'qty'       => $item->getQty(),
                                    'price'     => $item->getPrice(),
                                    'row_total' => $item->getRowTotal()
                                ];
                            }
                        } catch (\Exception $e) {
                            // If snapshot fails, skip logging but continue cart update/validation
                            $originalQuoteData = null;
                        }

                        try {
                            // Threshold validation is needed only when a discount exists
                            $hasDiscount = $this->discountThresholdService->hasDiscount($quoteId);
                            if ($hasDiscount) {
                                $amastyQuoteId = $quoteId;
                                // Build current items array (what the quote will look like after update)
                                // Include ALL items from the quote, using updated qty from cartData if available
                                // Include original_qty_when_discount_applied from each item
                                $currentItemsForValidation = [];
                                
                                // Get all quote items once (cache to avoid multiple calls)
                                $quoteItems = $quote->getAllItems();
                                
                                // Batch fetch original_qty_when_discount_applied for all items at once
                                $itemIds = [];
                                foreach ($quoteItems as $quoteItem) {
                                    if ($quoteItem->getParentItemId()) {
                                        continue;
                                    }
                                    $itemId = (int)$quoteItem->getId();
                                    if ($itemId) {
                                        $itemIds[] = $itemId;
                                    }
                                }
                                
                                // Fetch all original quantities in one query (batch optimization - reduces N queries to 1)
                                $originalQuantities = [];
                                if (!empty($itemIds)) {
                                    $resourceConnection = $this->discountThresholdService->getResourceConnection();
                                    $connection = $resourceConnection->getConnection();
                                    $itemTable = $resourceConnection->getTableName('quote_item');
                                    $select = $connection->select()
                                        ->from($itemTable, ['item_id', 'original_qty_when_discount_applied'])
                                        ->where('item_id IN (?)', $itemIds);
                                    $results = $connection->fetchAll($select);
                                    foreach ($results as $row) {
                                        $originalQuantities[(int)$row['item_id']] = (float)($row['original_qty_when_discount_applied'] ?? 0);
                                    }
                                }
                                
                                // Build validation array using cached data
                                foreach ($quoteItems as $quoteItem) {
                                    if ($quoteItem->getParentItemId()) {
                                        continue; // Skip child items
                                    }
                                    
                                    $itemId = (int)$quoteItem->getId();
                                    
                                    // Check if this item has an update in cartData
                                    if (isset($cartData[$itemId]) && isset($cartData[$itemId]['qty'])) {
                                        // Use updated quantity from cartData
                                        $rawQty = trim($cartData[$itemId]['qty']);
                                        $newQty = (float)$this->getLocateFilter()->filter($rawQty);
                                    } else {
                                        // Use current quantity from quote item
                                        $newQty = (float)$quoteItem->getQty();
                                    }
                                    
                                    // Only include items with qty > 0
                                    if ($newQty > 0) {
                                        // Get original_qty from cached array
                                        $originalQty = $originalQuantities[$itemId] ?? 0;
                                        
                                        $currentItemsForValidation[$itemId] = [
                                            'qty' => $newQty,
                                            'row_total' => (float)$quoteItem->getRowTotal(),
                                            'original_qty' => $originalQty
                                        ];
                                    }
                                }

                                // Validate changes against original_qty_when_discount_applied stored at line item level
                                $validation = $this->discountThresholdService->validateChanges($amastyQuoteId, $currentItemsForValidation);
                                
                                if (!$validation['allowed']) {
                                    // Check if we should show pop-up to remove discount
                                    if (isset($validation['remove_discount']) && $validation['remove_discount']) {
                                        // Check if this is an AJAX request
                                        $isAjax = $this->getRequest()->isAjax() 
                                            || $this->getRequest()->getHeader('X-Requested-With') === 'XMLHttpRequest';
                                        
                                        if ($isAjax) {
                                            // Return JSON response with popup flag
                                            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                                            return $resultJson->setData([
                                                'show_discount_removal_popup' => true,
                                                'message' => $validation['popup_message'] ?? __('Your discount will be removed if you proceed with these changes. Do you want to continue?'),
                                                'quote_id' => $amastyQuoteId,
                                                'cart_data' => $cartData
                                            ]);
                                        } else {
                                            // For non-AJAX requests, throw exception
                                            $validationException = new LocalizedException($validation['message']);
                                        }
                                    } else {
                                        // Old behavior - throw exception
                                        $validationException = new LocalizedException($validation['message']);
                                    }
                                }
                            }
                        } catch (LocalizedException $e) {
                            $validationException = $e;
                        } catch (\Exception $e) {
                            // Continue if validation fails
                        }
                    }
                } catch (\Exception $e) {
                    $isAmastyQuote = false;
                }
                
                if ($validationException) {
                    throw $validationException;
                }
                
                $quoteItems = [];
                foreach ($cartData as $index => &$data) {
                    if (isset($data['qty'])) {
                        $cartData[$index]['qty'] = $this->getLocateFilter()->filter(trim($data['qty']));
                    }
                    /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
                    $quoteItem = $quote->getItemById($index);
                    $quoteItems[] = $quoteItem;
                    if (!$quoteItem) {
                        throw new LocalizedException(__('Something went wrong'));
                    }

                    $price = isset($data['price'])
                        ? $this->convertPriceToBase($this->getLocateFilter()->filter(trim($data['price'])))
                        : $quoteItem->getPrice();
                    if (!$this->getConfigHelper()->isAllowCustomizePrice()
                        && $this->getHidePriceProvider()->isHidePrice($quoteItem->getProduct())
                    ) {
                        $price = 0;
                    }
                    $data['price'] = $this->convertPriceToCurrent($price);
                    if (isset($data['qty']) && !$this->getHidePriceProvider()->isHidePrice($quoteItem->getProduct())) {
                        $productFinalPrice = $quoteItem->getPrice();
                        // $productFinalPrice = $quoteItem->getProduct()->getFinalPrice(
                        //     $this->getLocateFilter()->filter(trim($data['qty']))
                        // );
                        $price = min($productFinalPrice, $price);
                    }

                    $quoteItem->setCustomPrice($price);
                    $quoteItem->setOriginalCustomPrice($price);

                    if (isset($data['note'])) {
                        $quote->getItemById($index)->setAdditionalData(
                            $this->cartHelper->updateAdditionalData(
                                $quote->getItemById($index)->getAdditionalData(),
                                [QuoteItemInterface::CUSTOMER_NOTE_KEY => trim($data['note'])]
                            )
                        );
                    }
                }

                if (!$this->cart->getCustomerSession()->getCustomerId()
                    && $this->cart->getQuote()->getCustomerId()
                ) {
                    $this->cart->getQuote()->setCustomerId(null);
                }

                $cartData = $this->cart->suggestItemsQty($cartData);
                $this->cart->updateItems($cartData);
                $this->cart->getQuote()->collectTotals();

                foreach ($quoteItems as $quoteItem) {
                    $quoteItem->setAdditionalData(
                        $this->cartHelper->updateAdditionalData(
                            $quoteItem->getAdditionalData(),
                            [
                                QuoteItemInterface::REQUESTED_PRICE => $quoteItem->getPrice(),
                                QuoteItemInterface::CUSTOM_PRICE => $cartData[$quoteItem->getId()]['price'],
                                QuoteItemInterface::HIDE_ORIGINAL_PRICE => $this->getHidePriceProvider()->isHidePrice(
                                    $quoteItem->getProduct()
                                )
                            ]
                        )
                    );
                }

                $this->cart->save();
                
                if ($isAmastyQuote && $quoteId) {
                    try {
                        $amastyQuote = $this->getQuoteRepository()->get($quoteId);
                        
                        if ($expDays = $this->amastyDataHelper->getExpirationTime()) {
                            $amastyQuote->setExpiredDate($this->amastyDateHelper->increaseDays($expDays));
                        }
                        
                        if ($remDays = $this->amastyDataHelper->getReminderTime()) {
                            $amastyQuote->setReminderDate($this->amastyDateHelper->increaseDays($remDays));
                        }
                        
                        $this->getQuoteRepository()->save($amastyQuote);
                    } catch (\Exception $e) {
                        // Continue if date update fails
                    }
                }
                
                if ($isAmastyQuote && $originalQuoteData) {
                    try {
                        // Use already loaded quote instead of reloading (performance optimization)
                        $updatedQuote = $quote;
                        $updatedQuoteItems = $updatedQuote->getAllItems();
                        
                        $newQuoteData = [
                            'subtotal' => $updatedQuote->getSubtotal(),
                            'grand_total' => $updatedQuote->getGrandTotal(),
                            'items_count' => count($updatedQuoteItems),
                            'items' => []
                        ];
                        
                        foreach ($updatedQuoteItems as $item) {
                            if ($item->getParentItemId()) {
                                continue;
                            }
                            $newQuoteData['items'][$item->getId()] = [
                                'item_id' => $item->getId(),
                                'sku' => $item->getSku(),
                                'qty' => $item->getQty(),
                                'price' => $item->getPrice(),
                                'row_total' => $item->getRowTotal()
                            ];
                        }
                        
                        foreach ($newQuoteData['items'] as $itemId => $newItem) {
                            if (isset($originalQuoteData['items'][$itemId])) {
                                $oldItem = $originalQuoteData['items'][$itemId];
                                if ($oldItem['qty'] != $newItem['qty'] || 
                                    $oldItem['price'] != $newItem['price']) {
                                    
                                    $this->quoteChangeLogger->logItemUpdated(
                                        $quoteId,
                                        (int)$itemId,
                                        [
                                            'sku' => $oldItem['sku'] ?? $newItem['sku'],
                                            'name' => $oldItem['name'] ?? $newItem['name'] ?? '',
                                            'qty' => $oldItem['qty'],
                                            'price' => $oldItem['price'],
                                            'row_total' => $oldItem['row_total'] ?? 0,
                                        ],
                                        [
                                            'sku' => $newItem['sku'],
                                            'name' => $newItem['name'] ?? '',
                                            'qty' => $newItem['qty'],
                                            'price' => $newItem['price'],
                                            'row_total' => $newItem['row_total'] ?? 0,
                                        ]
                                    );
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Continue if logging fails
                    }
                }
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage(
                $this->getEscaper()->escapeHtml($e->getMessage())
            );
            $resultJson->setData([
                'errors' => true,
                'message' => $e->getMessage()
            ]);
            return $resultJson;
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t update the shopping cart.'));
            $resultJson->setData([
                'errors' => true,
                'message' => __('We can\'t update the shopping cart.')
            ]);
            return $resultJson;
        }

        $resultJson->setData([
            'message' => 'Product is saved.',
            "redirect" => true,
            'url' => $this->_url->getUrl('request_quote/account/index')
        ]);
        return $resultJson;
    }

    /**
     * @param $price
     * @return float|int
     */
    private function convertPriceToBase($price)
    {
        $store = $this->getCheckoutSession()->getQuote()->getStore();
        $rate = $store->getBaseCurrency()->getRate(
            $this->priceCurrency->getCurrency($store)
        );

        if ($rate != 1) {
            $price = (float)$price / (float)$rate;
        }

        return $price;
    }

    /**
     * @param $price
     * @return float
     */
    private function convertPriceToCurrent($price)
    {
        return $this->priceCurrency->convert($price);
    }

    /**
     * @return void
     */
    protected function submitAction()
    {
        // Only run "discount carried over" logic for the dedicated Checkout Later flow.
        // NOTE: `checkout=context` is also present on the normal "Save Cart" form, so we MUST use
        // a dedicated flag that only the Checkout Later button sends.
        $isCheckoutLater = ((string)$this->getRequest()->getParam('checkout_later')) === '1';

        // Get quote from checkout session
        $quote = $this->checkoutSession->getQuote();
        
        $sessionQuoteId = $quote->getId();
        if ($sessionQuoteId) {
            try {
                $quote = $this->getQuoteRepository()->get((int)$sessionQuoteId);
            } catch (\Exception $e) {
                // Continue with session quote if reload fails
            }
        }
        
        $relationParentIdFromRequest = $this->getRequest()->getParam('relation_parent_id');
        $relationParentIdFromQuote = $quote->getRelationParentId();
        $relationParentId = $relationParentIdFromRequest ?: $relationParentIdFromQuote;
        if ($relationParentId) {
            $relationParentId = (int)$relationParentId;
        }
        $newQuoteId = $quote->getId();
        $originalStatus = null;
        $originalIncrementId = null;
        
        if ($relationParentId) {
            try {
                $originalQuote = $this->getQuoteRepository()->get((int)$relationParentId);
                $originalStatus = $originalQuote->getStatus();
                $originalIncrementId = $originalQuote->getIncrementId();
                
                $originalQuote->setId((int)$relationParentId);
                $originalQuote->setEntityId((int)$relationParentId);
                $originalQuote->setIsObjectNew(false);
                if ($originalIncrementId) {
                    $originalQuote->setOrigData('increment_id', $originalIncrementId);
                    $originalQuote->setOrigData('entity_id', (int)$relationParentId);
                }
                
                $allItems = $originalQuote->getAllItems();
                foreach ($allItems as $item) {
                    $originalQuote->deleteItem($item);
                }
                
                $this->getQuoteRepository()->save($originalQuote);
                $originalQuote = $this->getQuoteRepository()->get((int)$relationParentId);
                
                $originalQuote->setId((int)$relationParentId);
                $originalQuote->setEntityId((int)$relationParentId);
                $originalQuote->setIsObjectNew(false);
                if ($originalIncrementId) {
                    $originalQuote->setOrigData('increment_id', $originalIncrementId);
                }
                $originalQuote->setOrigData('entity_id', (int)$relationParentId);
                
                foreach ($quote->getAllVisibleItems() as $item) {
                    $newItem = clone $item;
                    $newItem->setId(null);
                    $newItem->setItemId(null);
                    $newItem->setQuoteId((int)$relationParentId);
                    $newItem->setQuote($originalQuote);
                    $newItem->setIsObjectNew(true);
                    $newItem->setParentItemId(null);
                    
                    if ($item->getHasChildren()) {
                        foreach ($item->getChildren() as $child) {
                            $newChild = clone $child;
                            $newChild->setId(null);
                            $newChild->setItemId(null);
                            $newChild->setQuoteId((int)$relationParentId);
                            $newChild->setQuote($originalQuote);
                            $newChild->setParentItemId(null);
                            $newChild->setIsObjectNew(true);
                            $newChild->setParentItem($newItem);
                        }
                    }
                    
                    $originalQuote->addItem($newItem);
                }
                
                if ($quote->getQuoteCustomerNote()) {
                    $originalQuote->setQuoteCustomerNote($quote->getQuoteCustomerNote());
                }
                if ($quote->getRemarks()) {
                    $originalQuote->setRemarks($quote->getRemarks());
                }
                
                // Copy discount from original quote to merged quote
                try {
                    $resourceConnection = $this->discountThresholdService->getResourceConnection();
                    $connection = $resourceConnection->getConnection();
                    $amastyQuoteTable = $resourceConnection->getTableName('amasty_quote');
                    
                    // Get discount from original quote
                    $select = $connection->select()
                        ->from($amastyQuoteTable, ['discount', 'surcharge'])
                        ->where('quote_id = ?', (int)$relationParentId)
                        ->limit(1);
                    
                    $discountData = $connection->fetchRow($select);
                    
                    if ($discountData && (isset($discountData['discount']) || isset($discountData['surcharge']))) {
                        $discount = isset($discountData['discount']) ? (float)$discountData['discount'] : 0;
                        $surcharge = isset($discountData['surcharge']) ? (float)$discountData['surcharge'] : 0;
                        
                        // Apply discount to the merged quote
                        if ($discount > 0 || $surcharge > 0) {
                            // Update or insert discount in amasty_quote table for the merged quote
                            $quoteIdForDiscount = (int)$relationParentId; // Use relationParentId as the quote ID
                            
                            // Check if record exists
                            $existingRecord = $connection->fetchOne(
                                $connection->select()
                                    ->from($amastyQuoteTable, ['quote_id'])
                                    ->where('quote_id = ?', $quoteIdForDiscount)
                                    ->limit(1)
                            );
                            
                            if ($existingRecord) {
                                // Update existing record
                                $connection->update(
                                    $amastyQuoteTable,
                                    [
                                        'discount' => $discount,
                                        'surcharge' => $surcharge
                                    ],
                                    ['quote_id = ?' => $quoteIdForDiscount]
                                );
                            } else {
                                // Insert new record (shouldn't happen, but handle it)
                                $connection->insert(
                                    $amastyQuoteTable,
                                    [
                                        'quote_id' => $quoteIdForDiscount,
                                        'discount' => $discount,
                                        'surcharge' => $surcharge
                                    ]
                                );
                            }
                            
                            // Also set discount on quote object for immediate use
                            $originalQuote->setDiscount($discount);
                            $originalQuote->setData('discount', $discount);
                        }
                    }
                } catch (\Exception $e) {
                    // Log error but continue - discount copy failure shouldn't break quote creation
                    $this->logger->warning('Failed to copy discount from original quote', [
                        'original_quote_id' => $relationParentId,
                        'error' => $e->getMessage()
                    ]);
                }
                
                $quote = $originalQuote;
                
            } catch (\Exception $e) {
                // Continue with session quote if load fails
            }
        }

        $this->_eventManager->dispatch('amasty_request_quote_submit_before', ['quote' => $quote]);

        $quote->setSubmitedDate($this->dateTime->gmtDate());
        
        if ($relationParentId && isset($originalStatus)) {
            $quote->setStatus((int)$originalStatus);
        } else {
            $quote->setStatus(Status::APPROVED);
        }
        
        if ($relationParentId) {
            if ($quote->getId() != $relationParentId) {
                $quote = $this->getQuoteRepository()->get((int)$relationParentId);
                $quote->getItemsCollection()->load();
            }
            
            $quote->setId((int)$relationParentId);
            $quote->setEntityId((int)$relationParentId);
            $quote->setIsObjectNew(false);
            $quote->setOrigData('entity_id', (int)$relationParentId);
            if (isset($originalIncrementId) && $originalIncrementId) {
                $quote->setOrigData('increment_id', $originalIncrementId);
            }
        }
        
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $this->getQuoteRepository()->save($quote);
        
        // Check if cart items came from an existing quote (via amasty_quote_id option)
        // This handles the case: Quote listing → Move to cart → Checkout Later (creates new quote)
        $originalQuoteIdForDiscount = null;
            if ($isCheckoutLater && !$relationParentId) {
            // No relation_parent_id, so this is a new quote - check if items came from an existing quote
            foreach ($quote->getAllItems() as $item) {
                if ($item->getParentItemId()) {
                    continue; // Skip child items
                }
                
                // Check if item has amasty_quote_id option (set when moved from quote to cart)
                $amastyQuoteIdOption = $item->getOptionByCode('amasty_quote_id');
                if ($amastyQuoteIdOption && $amastyQuoteIdOption->getValue()) {
                    $originalQuoteIdForDiscount = (int)$amastyQuoteIdOption->getValue();
                    break; // Use first found quote ID (all items should be from same quote)
                }
            }
        }
        
        // If discount was copied from original quote, apply it to items and recalculate prices
        // This handles both: relationParentId (editing existing quote) and originalQuoteIdForDiscount (new quote from cart)
            $quoteIdToCheckForDiscount = $relationParentId ?: $originalQuoteIdForDiscount;
            if ($isCheckoutLater && $quoteIdToCheckForDiscount) {
            try {
                $resourceConnection = $this->discountThresholdService->getResourceConnection();
                $connection = $resourceConnection->getConnection();
                $amastyQuoteTable = $resourceConnection->getTableName('amasty_quote');
                
                // Get discount and remarks from original quote
                $originalQuoteData = $connection->fetchRow(
                    $connection->select()
                        ->from($amastyQuoteTable, ['discount', 'surcharge', 'remarks'])
                        ->where('quote_id = ?', (int)$quoteIdToCheckForDiscount)
                        ->limit(1)
                );
                
                if ($originalQuoteData && (isset($originalQuoteData['discount']) || isset($originalQuoteData['surcharge']))) {
                    $discount = isset($originalQuoteData['discount']) ? (float)$originalQuoteData['discount'] : 0;
                    $surcharge = isset($originalQuoteData['surcharge']) ? (float)$originalQuoteData['surcharge'] : 0;
                    
                    // If discount exists, copy it to the new quote
                    if ($discount > 0 || $surcharge > 0) {
                        $newQuoteId = $quote->getId();
                        
                        // Prepare remarks with admin_note
                        $remarks = null;
                        $remarksJson = $originalQuoteData['remarks'] ?? null;
                        
                        // Get existing remarks from original quote or create new
                        if ($remarksJson) {
                            $decodedRemarks = json_decode($remarksJson, true);
                            if (is_array($decodedRemarks)) {
                                $remarks = $decodedRemarks;
                            }
                        }
                        
                        // If remarks doesn't exist, create new array
                        if (!is_array($remarks)) {
                            $remarks = [];
                        }
                        
                        // Copy admin_note from original quote if it exists, otherwise create new one
                        if (!isset($remarks['admin_note']) || empty($remarks['admin_note'])) {
                            // Create new admin_note with discount message
                            if ($discount > 0) {
                                $remarks['admin_note'] = __('Additional Discount in amount of %1% was applied.', $discount)->render();
                            } elseif ($surcharge > 0) {
                                $remarks['admin_note'] = __('Additional Surcharge in amount of %1% was applied.', $surcharge)->render();
                            }
                        }
                        // If admin_note already exists in original quote, it will be preserved in $remarks
                        
                        // Copy discount to new quote's amasty_quote table
                        $existingRecord = $connection->fetchOne(
                            $connection->select()
                                ->from($amastyQuoteTable, ['quote_id'])
                                ->where('quote_id = ?', $newQuoteId)
                                ->limit(1)
                        );
                        
                        $updateData = [
                            'discount' => $discount,
                            'surcharge' => $surcharge
                        ];
                        
                        // Add remarks if we have admin_note
                        if (!empty($remarks['admin_note'])) {
                            $updateData['remarks'] = json_encode($remarks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
                        
                        if ($existingRecord) {
                            // Update existing record
                            $connection->update(
                                $amastyQuoteTable,
                                $updateData,
                                ['quote_id = ?' => $newQuoteId]
                            );
                        } else {
                            // Insert new record
                            $connection->insert(
                                $amastyQuoteTable,
                                array_merge(
                                    [
                                        'quote_id' => $newQuoteId
                                    ],
                                    $updateData
                                )
                            );
                        }
                        
                        // Reload quote to ensure we have latest data
                        $quoteToRecalculate = $this->getQuoteRepository()->get($newQuoteId);
                        $quoteToRecalculate->getItemsCollection()->load();
                        
                        // Set discount on quote object
                        $quoteToRecalculate->setDiscount($discount);
                        $quoteToRecalculate->setData('discount', $discount);
                        
                        // Apply discount to items using ViewModel (same logic as admin save)
                        $this->requestQuoteViewModel->recalculateQuotePrices($quoteToRecalculate, true);
                        
                        // Save quote with updated prices
                        $this->getQuoteRepository()->save($quoteToRecalculate);
                        
                        // Log discount carryover in Quote Change History ONLY for:
                        // Quote listing → Move to cart → "Checkout Later" (new quote created from cart items).
                        // Do NOT log it when simply editing/submitting an existing quote via relation_parent_id flow.
                        if ($isCheckoutLater && !$relationParentId && $originalQuoteIdForDiscount) {
                            try {
                                $this->quoteChangeLogger->logChange(
                                    (int)$newQuoteId,
                                    QuoteChangeLogInterface::ACTION_DISCOUNT_CARRIED_OVER,
                                    null,
                                    [
                                        'discount' => $discount,
                                        'surcharge' => $surcharge,
                                        'from_quote_id' => (int)$originalQuoteIdForDiscount,
                                        'message' => __('Discount carried over from quote #%1', (int)$originalQuoteIdForDiscount)->render()
                                    ]
                                );
                            } catch (\Exception $logException) {
                                // Log error but continue - logging failure shouldn't break quote creation
                                $this->logger->warning('Failed to log discount carryover', [
                                    'quote_id' => $newQuoteId,
                                    'from_quote_id' => (int)$originalQuoteIdForDiscount,
                                    'error' => $logException->getMessage()
                                ]);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue - price recalculation failure shouldn't break quote creation
                $this->logger->warning('Failed to apply discount to quote items', [
                    'quote_id' => $quoteIdToCheckForDiscount,
                    'new_quote_id' => $quote->getId(),
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $quoteIdToReload = $quote->getId();
        if (isset($relationParentId) && $relationParentId) {
            $quoteIdToReload = (int)$relationParentId;
        }
        
        $quote = $this->getQuoteRepository()->get($quoteIdToReload);
        $quoteItems = $quote->getAllItems();
        
        foreach ($quoteItems as $quoteItem) {
            if (!$quoteItem->getId()) {
                continue;
            }
            
            $priceOption = $this->dataObjectFactory->create(
                []
            )->setCode(
                'amasty_quote_price'
            )->setValue(
                $quoteItem->getPrice()
            )->setProduct(
                $quoteItem->getProduct()
            );
            $quoteItem->addOption($priceOption)->saveItemOptions();
        }
        
        $this->registry->register(RegistryConstants::AMASTY_QUOTE, $quote);
        
        if($quote->getCustomerIsGuest() == 0){
            $this->notifyCustomer();
        }
        
        // Use the final quote ID (which might be relationParentId if editing an existing quote)
        $quoteId = $quote->getId();
        
        // Only call notifyAdmin if quote ID exists and is a valid integer (not object/array)
        if (is_scalar($quoteId) && $quoteId !== '' && $quoteId !== null && is_numeric($quoteId) && (int)$quoteId > 0) {
            $quoteIdInt = (int)$quoteId;
            
            // Verify the quote exists and can be loaded before notifying
            try {
                $quoteToNotify = $this->getQuoteRepository()->get($quoteIdInt);
                $quoteStatus = $quoteToNotify->getStatus();
                $adminNotificationSend = $quoteToNotify->getData('admin_notification_send');
                
                // The email grid filters quotes by:
                // 1. Status must NOT be CREATED (0) or ADMIN_NEW (7)
                // 2. admin_notification_send must be NOT_SENT (0)
                // So we need to ensure the quote meets these criteria
                $statusCreated = Status::CREATED; // 0
                $statusAdminNew = Status::ADMIN_NEW; // 7
                $canNotify = ($quoteStatus != $statusCreated && $quoteStatus != $statusAdminNew) && ($adminNotificationSend == 0 || $adminNotificationSend === null);
                
                if ($canNotify) {
                    $this->notifyAdmin($quoteIdInt);
                }
            } catch (\Exception $e) {
                $this->getLogger()->error('Quote not found for notification - ID: ' . $quoteIdInt . ', Error: ' . $e->getMessage());
            }
        }

        $proceedToCheckout = ((string)$this->getRequest()->getParam('proceed_to_checkout')) === '1';

        // For proceed_to_checkout: use normal save flow (clear session) - Move to Cart will merge into fresh cart
        $this->checkoutSession->setLastQuoteId($this->checkoutSession->getQuoteId());
        $this->checkoutSession->setQuoteId(null);
        // Clear cached _quote so getQuote() won't return stale quote; storage already has quote_id=null
        try {
            $checkoutReflection = new \ReflectionClass($this->checkoutSession);
            if ($checkoutReflection->hasProperty('_quote')) {
                $prop = $checkoutReflection->getProperty('_quote');
                $prop->setAccessible(true);
                $prop->setValue($this->checkoutSession, null);
            }
        } catch (\Exception $e) {
            // Continue if reflection fails
        }
        
        try {
            $this->amastyQuoteSession->setQuoteId(null);
            
            $reflection = new \ReflectionClass($this->amastyQuoteSession);
            if ($reflection->hasProperty('_quote')) {
                $property = $reflection->getProperty('_quote');
                $property->setAccessible(true);
                $property->setValue($this->amastyQuoteSession, null);
            }
        } catch (\Exception $e) {
            // Continue if Amasty quote session is not available
        }
        
        try {
            $cartQuote = $this->checkoutSession->getQuote();
            if ($cartQuote && $cartQuote->getId()) {
                if ($cartQuote->getId() != $quote->getId()) {
                    $cartQuote->removeAllItems();
                    $cartQuote->setTotalsCollectedFlag(false);
                    $cartQuote->collectTotals();
                    $this->cart->save();
                } else {
                    $cartQuote->removeAllItems();
                }
            }
        } catch (\Exception $e) {
            // Continue if cart clearing fails
        }

        $this->_eventManager->dispatch('amasty_request_quote_submit_after', ['quote' => $quote]);

        // After any quote submit (Save Cart, Checkout Later): show cart icon and force quotecart section refresh
        $this->checkoutSession->setData(ActiveCartIcon::getSessionKey(), ActiveCartIcon::getTypeCart());
        try {
            $this->amastyQuoteSession->flushSection('quotecart');
        } catch (\Exception $e) {
            // Continue if flush fails
        }
        
        // CRITICAL: Delete the temporary new quote AFTER all operations are complete
        if ($relationParentId && isset($newQuoteId) && $newQuoteId && $newQuoteId != $relationParentId) {
            try {
                $tempQuote = $this->getQuoteRepository()->get((int)$newQuoteId);
                $this->getQuoteRepository()->delete($tempQuote);
            } catch (\Exception $e) {
                // Continue if deletion fails
            }
        }
    }

    /**
     * Update existing quote with items from cart quote
     *
     * @param \Amasty\RequestQuote\Api\Data\QuoteInterface $originalQuote
     * @param \Amasty\RequestQuote\Api\Data\QuoteInterface $cartQuote
     * @return void
     */
    private function updateExistingQuote($originalQuote, $cartQuote)
    {
        foreach ($originalQuote->getAllItems() as $item) {
            $originalQuote->removeItem($item->getItemId());
        }

        foreach ($cartQuote->getAllVisibleItems() as $item) {
            $newItem = clone $item;
            $newItem->setId(null);
            $newItem->setItemId(null);
            $newItem->setQuoteId($originalQuote->getId());
            $newItem->setQuote($originalQuote);
            $newItem->setPrice($item->getPrice());
            $newItem->setBasePrice($item->getBasePrice());
            $newItem->setCustomPrice($item->getCustomPrice());
            $newItem->setOriginalCustomPrice($item->getOriginalCustomPrice());
            $newItem->setQty($item->getQty());
            
            if ($item->getAdditionalData()) {
                $newItem->setAdditionalData($item->getAdditionalData());
            }
            
            $originalQuote->addItem($newItem);
            
            if ($item->getHasChildren()) {
                foreach ($item->getChildren() as $child) {
                    $newChild = clone $child;
                    $newChild->setId(null);
                    $newChild->setItemId(null);
                    $newChild->setQuoteId($originalQuote->getId());
                    $newChild->setQuote($originalQuote);
                    $newChild->setPrice($child->getPrice());
                    $newChild->setBasePrice($child->getBasePrice());
                    $newChild->setQty($child->getQty());
                    $newChild->setParentItem($newItem);
                    $originalQuote->addItem($newChild);
                }
            }
        }

        if ($cartQuote->getQuoteCustomerNote()) {
            $originalQuote->setQuoteCustomerNote($cartQuote->getQuoteCustomerNote());
        }

        $originalQuote->setTotalsCollectedFlag(false);
        $originalQuote->collectTotals();
        $this->getQuoteRepository()->save($originalQuote);
        
        $originalQuote = $this->getQuoteRepository()->get($originalQuote->getId());
        $items = $originalQuote->getAllItems();
        if (count($items) > 0) {
            $originalQuote->setTotalsCollectedFlag(false);
            $originalQuote->collectTotals();
            $this->getQuoteRepository()->save($originalQuote);
        }
    }

    /**
     * @return void
     */
    private function notifyCustomer()
    {
        $quote = $this->checkoutSession->getQuote();
        $quote['created_date_formatted'] = $quote->getCreatedAtFormatted(\IntlDateFormatter::MEDIUM);
        $this->emailSender->sendEmail(
            Data::CONFIG_PATH_CUSTOMER_SUBMIT_EMAIL,
            $this->getCustomerSession()->getCustomer()->getEmail(),
            [
                'viewUrl' => $this->_url->getUrl(
                    'amasty_quote/account/view',
                    ['quote_id' => $this->checkoutSession->getQuoteId()]
                ),
                'quote' => $quote,
                'remarks' => $this->cartHelper->retrieveCustomerNote($this->checkoutSession->getQuote()->getRemarks())
            ]
        );
    }

    /**
     * @param int $quoteId
     */
    private function notifyAdmin($quoteId)
    {
        if ($this->getConfigHelper()->isAdminNotificationsInstantly()) {
            $this->getAdminNotification()->sendNotification([$quoteId]);
        }
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        ob_start();
        try {
            return $this->processExecute();
        } finally {
            if (ob_get_level()) {
                ob_end_clean();
            }
        }
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    private function processExecute()
    {
        $updateAction = (string)$this->getRequest()->getParam('update_cart_action');
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $resultJson->setData([
                'errors' => true,
                'message' => __('Invalid security or form key. Please refresh the page.')
            ]);
            return $resultJson;
        }

        switch ($updateAction) {
            case 'empty_cart':
                $this->cart->truncate()->save();
                $resultJson->setData([
                    "redirect" => true,
                    'url' => $this->_url->getUrl('amasty_quote/quote/cart')
                ]);
                return $resultJson;
            case 'update_qty':
                return $this->_updateShoppingCart();
            case 'submit':
                if (($email = $this->getRequest()->getParam('email', null))
                    && !$this->getConfigHelper()->isLoggedIn()
                ) {
                    try {
                        $this->login();
                    } catch (LocalizedException $e) {
                        $this->messageManager->addErrorMessage($e->getMessage());
                        $resultJson->setData([
                            'errors' => true,
                            'message' => $e->getMessage()
                        ]);
                        return $resultJson;
                    } catch (Exception $e) {
                        $this->messageManager->addErrorMessage(__('Something went wrong'));
                        $this->getLogger()->error($e->getMessage());
                        $resultJson->setData([
                            'errors' => true,
                            'message' => __('Something went wrong')
                        ]);
                        return $resultJson;
                    }
                }
                
                $cartData = $this->getRequest()->getParam('cart');
                
                // Try multiple ways to get cart data
                if (!$cartData || !is_array($cartData)) {
                    // Try getting from POST directly
                    $postData = $this->getRequest()->getPostValue();
                    $cartData = $postData['cart'] ?? null;
                }
                
                // If still no cart data, try getting all params
                if (!$cartData || !is_array($cartData)) {
                    $allParams = $this->getRequest()->getParams();
                    $cartData = $allParams['cart'] ?? null;
                }
                
                // Validate discount threshold with current quote state (quantities should be updated by update_qty action)
                if (is_array($cartData) && !empty($cartData)) {
                    try {
                        $quote = $this->checkoutSession->getQuote();
                        $quoteId = (int)$quote->getId();
                        
                        if ($quoteId) {
                            $quoteRepository = $this->getQuoteRepository();
                            $isAmastyQuote = false;
                            
                            try {
                                if (method_exists($quoteRepository, 'isAmastyQuote')) {
                                    $isAmastyQuote = $quoteRepository->isAmastyQuote($quoteId);
                                } else {
                                    try {
                                        $quoteRepository->get($quoteId);
                                        $isAmastyQuote = true;
                                    } catch (\Exception $e) {
                                        $isAmastyQuote = false;
                                    }
                                }
                                
                                if ($isAmastyQuote) {
                                    // Load the original quote (the one being edited)
                                    $relationParentId = $this->getRequest()->getParam('relation_parent_id');
                                    if (!$relationParentId) {
                                        $relationParentId = $quote->getRelationParentId();
                                    }
                                    
                                    if ($relationParentId) {
                                        $originalQuote = $quoteRepository->get((int)$relationParentId);
                                        $originalQuote->getItemsCollection()->load();
                                        
                                        // Build original items array (from saved quote) - keyed by SKU
                                        $originalItemsBySku = [];
                                        $originalItemsById = [];
                                        foreach ($originalQuote->getAllItems() as $item) {
                                            if ($item->getParentItemId()) {
                                                continue;
                                            }
                                            $sku = $item->getSku();
                                            $originalItemsBySku[$sku] = [
                                                'item_id' => $item->getId(),
                                                'qty' => (float)$item->getQty(),
                                                'row_total' => (float)$item->getRowTotal()
                                            ];
                                            $originalItemsById[$item->getId()] = [
                                                'qty' => (float)$item->getQty(),
                                                'row_total' => (float)$item->getRowTotal()
                                            ];
                                        }
                                        
                                        // Build new items array from UPDATED quote (after cart update)
                                        // Reload quote to get updated quantities
                                        $updatedQuote = $this->checkoutSession->getQuote();
                                        $updatedQuote->getItemsCollection()->load();
                                        
                                        $currentItemsForValidation = [];
                                        foreach ($updatedQuote->getAllItems() as $item) {
                                            if ($item->getParentItemId()) {
                                                continue;
                                            }
                                            $sku = $item->getSku();
                                            if (isset($originalItemsBySku[$sku])) {
                                                // Use original item ID for validation
                                                $originalItemId = $originalItemsBySku[$sku]['item_id'];
                                                $newQty = (float)$item->getQty();
                                                
                                                // Only add if qty is valid
                                                if ($newQty > 0) {
                                                    $originalQty = $this->discountThresholdService->getOriginalQtyFromDatabase($originalItemId);
                                                    if ($originalQty <= 0) {
                                                        $originalQty = (float)($originalItemsById[$originalItemId]['qty'] ?? 0);
                                                    }
                                                    $currentItemsForValidation[$originalItemId] = [
                                                        'qty' => $newQty,
                                                        'row_total' => (float)$item->getRowTotal(),
                                                        'original_qty' => $originalQty
                                                    ];
                                                }
                                            }
                                        }
                                        
                                        // Validate changes (uses original_qty_when_discount_applied for threshold)
                                        $amastyQuoteId = (int)$originalQuote->getId();
                                        $validation = $this->discountThresholdService->validateChanges($amastyQuoteId, $currentItemsForValidation);
                                        
                                        if (!$validation['allowed']) {
                                            // If validation suggests removing discount with popup, return JSON for AJAX requests
                                            if (!empty($validation['remove_discount'])) {
                                                $isAjax = $this->getRequest()->isAjax()
                                                    || $this->getRequest()->getHeader('X-Requested-With') === 'XMLHttpRequest';
                                                
                                                if ($isAjax) {
                                                    $resultJson->setData([
                                                        'show_discount_removal_popup' => true,
                                                        'message' => $validation['popup_message']
                                                            ?? __('Your discount will be removed if you proceed with these changes. Do you want to continue?'),
                                                        'quote_id' => $amastyQuoteId
                                                    ]);
                                                    return $resultJson;
                                                }
                                            }
                                            
                                            // Fallback: old behaviour - just return error
                                            $errorMessage = $validation['message'];
                                            $this->messageManager->addErrorMessage($errorMessage);
                                            $resultJson->setData([
                                                'errors' => true,
                                                'message' => $errorMessage
                                            ]);
                                            return $resultJson;
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                // If validation fails, continue (don't block submission)
                            }
                        }
                    } catch (\Exception $e) {
                        // If validation check fails, continue (don't block submission)
                    }
                }
                
                try {
                    $this->submitAction();
                    
                    $proceedToCheckout = ((string)$this->getRequest()->getParam('proceed_to_checkout')) === '1';
                    
                    // Add success message via messageManager (will be displayed on redirect page)
                    $this->messageManager->addSuccessMessage(
                        $proceedToCheckout
                            ? __('Your cart has been saved. Proceeding to checkout.')
                            : __('Your quote has been submitted successfully.')
                    );
                    
                    if ($proceedToCheckout) {
                        // Execute Move to Cart in same request - add items to cart then redirect to checkout
                        $quoteId = $this->getRequest()->getParam('relation_parent_id')
                            ?: $this->checkoutSession->getLastQuoteId();
                        if (!$quoteId) {
                            $resultJson->setData([
                                'errors' => true,
                                'message' => __('Unable to proceed. Please try again.')
                            ]);
                            return $resultJson;
                        }
                        // Clear cart before Move to Cart so the "already has quote" check passes
                        $this->cart->truncate()->save();
                        $this->moveToCartService->execute((int)$quoteId, true);
                        $resultJson->setData([
                            'redirect' => true,
                            'url' => $this->_url->getUrl('checkout')
                        ]);
                    } else {
                        $resultJson->setData([
                            'redirect' => true,
                            'url' => $this->urlResolver->getSuccessUrl()
                        ]);
                    }
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                    $resultJson->setData([
                        'errors' => true,
                        'message' => $e->getMessage()
                    ]);
                } catch (Exception $e) {
                    $this->messageManager->addExceptionMessage($e, __('We can\'t submit the quote.'));
                    $resultJson->setData([
                        'errors' => true,
                        'message' => __('We can\'t submit the quote.')
                    ]);
                }
                return $resultJson;
            default:
                $this->_updateShoppingCart();
        }
        return $resultJson;
    }

    /**
     * @throws LocalizedException
     * @throws InputException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function login()
    {
        $customer = $this->getCustomerExtractor()->extract('customer_account_create', $this->getRequest());
        /** @var CustomerInterface $customer */
        $customer = $this->getAccountManagement()->createAccount($customer);
        $this->_eventManager->dispatch(
            'customer_register_success',
            ['account_controller' => $this, 'customer' => $customer]
        );

        $confirmationStatus = $this->getAccountManagement()->getConfirmationStatus($customer->getId());
        if ($confirmationStatus === CustomerAccountManagement::ACCOUNT_CONFIRMATION_REQUIRED) {
            $this->messageManager->addComplexSuccessMessage(
                'confirmAccountSuccessMessage',
                [
                    'url' => $this->getCustomerUrl()->getEmailConfirmationUrl($customer->getEmail()),
                ]
            );
        }
		
		// Try to send email, but don't fail quote submission if email fails
		try {
			$senderemail = $this->scopeConfig->getValue('trans_email/ident_general/email');
			$sendername = $this->scopeConfig->getValue('trans_email/ident_general/name');
			$sender = [
				'name' => $sendername,
				'email' => $senderemail,
			];
			
			$transportBuilder = $this->transportBuilder;
			$quote = $this->checkoutSession->getQuote();
			$quote['created_date_formatted'] = $quote->getCreatedAtFormatted(\IntlDateFormatter::MEDIUM);
			$transport = $transportBuilder->setTemplateIdentifier('amasty_request_quote_customer_notifications_customer_template_submit'); // Template identifier
			
			$transport->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => 1])
			  ->setTemplateVars([
	            'quote' => $quote,
	            'customerName' => $customer->getFirstname() . ' ' . $customer->getLastname(),
				'viewUrl' => $this->_url->getUrl(
	                    'amasty_quote/account/view',
	                    ['quote_id' => $this->checkoutSession->getQuoteId()]
	             ),
				'remarks' => $this->cartHelper->retrieveCustomerNote($this->checkoutSession->getQuote()->getRemarks())
	        ]) ->setFrom($sender)
			  ->addTo($customer->getEmail());

			$transport->getTransport()->sendMessage();
		} catch (\Exception $e) {
			// Log email error but don't fail quote submission
			$this->getLogger()->error('Failed to send quote submission email to guest: ' . $e->getMessage());
		}
		
        if ($customer && $this->authenticate($customer)) {
            $this->refresh($customer);
            $this->checkoutSession->getQuote()->setCustomer($customer);
        }
    }

    /**
     * @param CustomerInterface $customer
     *
     * @return bool
     */
    private function authenticate($customer)
    {
        $customerId = $customer->getId();
        if ($this->getAuthentication()->isLocked($customerId)) {
            $this->messageManager->addErrorMessage(__('The account is locked.'));
            return false;
        }

        $this->getAuthentication()->unlock($customerId);
        $this->_eventManager->dispatch('customer_data_object_login', ['customer' => $customer]);

        return true;
    }

    /**
     * @param CustomerInterface $customer
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    private function refresh($customer)
    {
        if ($customer && $customer->getId()) {
            $this->_eventManager->dispatch('amquote_customer_authenticated');
            $this->getCustomerSession()->setCustomerDataAsLoggedIn($customer);
            $this->getCustomerSession()->regenerateId();
            $this->getCheckoutSession()->loadCustomerQuote();

            if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                $metadata->setPath('/');
                $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
            }
        }
    }
}
