<?php
declare(strict_types=1);

namespace Dcw\RequestQuote\ViewModel;

use Amasty\RequestQuote\Model\Quote\Session as AmastyQuoteSession;
use Amasty\RequestQuote\Model\QuoteRepository;
use Dcw\RequestQuote\Model\ValidateQuoteStatus;
use Dcw\RequestQuote\Service\QuoteLockService;
use Dcw\RequestQuote\Model\ResourceModel\QuoteLock\CollectionFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\RequestInterface;
use Dcw\AdvanceSearch\ViewModel\Data as AdvanceSearchData;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Psr\Log\LoggerInterface;

/**
 * ViewModel for RequestQuote module
 * Provides common functionality for use in PHTML templates
 */
class Data implements ArgumentInterface
{
    private const XML_PATH_SHOW_SAVE_CART_PROCEED_TO_CHECKOUT = 'requestquote/general/show_save_cart_proceed_to_checkout';

    /**
     * @param AmastyQuoteSession $amastyQuoteSession
     * @param QuoteRepository $quoteRepository
     * @param ValidateQuoteStatus $validateQuoteStatus
     * @param QuoteLockService $quoteLockService
     * @param SessionManagerInterface $sessionManager
     * @param CollectionFactory $quoteLockCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param RequestInterface $request
     * @param AdvanceSearchData $advanceSearchData
     * @param ProductRepositoryInterface $productRepository
     * @param SerializerInterface $serializer
     * @param PricingHelper $pricingHelper
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly AmastyQuoteSession $amastyQuoteSession,
        private readonly QuoteRepository $quoteRepository,
        private readonly ValidateQuoteStatus $validateQuoteStatus,
        private readonly QuoteLockService $quoteLockService,
        private readonly SessionManagerInterface $sessionManager,
        private readonly CollectionFactory $quoteLockCollectionFactory,
        private readonly ResourceConnection $resourceConnection,
        private readonly RequestInterface $request,
        private readonly AdvanceSearchData $advanceSearchData,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SerializerInterface $serializer,
        private readonly PricingHelper $pricingHelper,
        private readonly LoggerInterface $logger,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Check if "Save Cart & Proceed to Checkout" button should be displayed
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isShowSaveCartProceedToCheckout(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_SAVE_CART_PROCEED_TO_CHECKOUT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Amasty Quote Session
     *
     * @return AmastyQuoteSession
     */
    public function getAmastyQuoteSession(): AmastyQuoteSession
    {
        return $this->amastyQuoteSession;
    }

    /**
     * Get Quote Repository
     *
     * @return QuoteRepository
     */
    public function getQuoteRepository(): QuoteRepository
    {
        return $this->quoteRepository;
    }

    /**
     * Get Validate Quote Status
     *
     * @return ValidateQuoteStatus
     */
    public function getValidateQuoteStatus(): ValidateQuoteStatus
    {
        return $this->validateQuoteStatus;
    }

    /**
     * Get current quote ID from session
     *
     * @return int|null
     */
    public function getCurrentQuoteId(): ?int
    {
        return $this->amastyQuoteSession->getQuoteId();
    }

    /**
     * Get current quote from session
     *
     * @return \Amasty\RequestQuote\Api\Data\QuoteInterface|null
     */
    public function getCurrentQuote()
    {
        try {
            $quoteId = $this->amastyQuoteSession->getQuoteId();
            if ($quoteId) {
                return $this->quoteRepository->get((int)$quoteId);
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }

    /**
     * Reload quote from repository to get latest data including updated totals
     *
     * @param int $quoteId
     * @return \Amasty\RequestQuote\Api\Data\QuoteInterface|null
     */
    public function reloadQuote(int $quoteId)
    {
        try {
            $quote = $this->quoteRepository->get($quoteId);
            $itemsCollection = $quote->getItemsCollection();
            $itemsCollection->clear();
            $itemsCollection->load();
            
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();
            
            return $quote;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error reloading quote',
                ['quote_id' => $quoteId, 'error' => $e->getMessage()]
            );
            return null;
        }
    }

    /**
     * Get quote cart items count
     *
     * @return int
     */
    public function getQuoteCartItemsCount(): int
    {
        try {
            $quote = $this->getCurrentQuote();
            if ($quote) {
                $quote->getItemsCollection()->load();
                return count($quote->getAllVisibleItems());
            }
        } catch (\Exception $e) {
            return 0;
        }
        return 0;
    }

    /**
     * Check if Amasty quote cart has items
     *
     * @return bool
     */
    public function hasQuoteItems(): bool
    {
        return $this->getQuoteCartItemsCount() > 0;
    }

    /**
     * Validate if quote can be edited
     *
     * @param \Amasty\RequestQuote\Api\Data\QuoteInterface $quote
     * @return bool
     */
    public function canEditQuote($quote): bool
    {
        return $this->validateQuoteStatus->validate($quote);
    }

    /**
     * Get quote by ID
     *
     * @param int $quoteId
     * @return \Amasty\RequestQuote\Api\Data\QuoteInterface|null
     */
    public function getQuoteById(int $quoteId)
    {
        try {
            return $this->quoteRepository->get($quoteId);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if quote is locked by admin
     *
     * @param int $quoteId
     * @return bool
     */
    public function isLockedByAdmin(int $quoteId): bool
    {
        try {
            $sessionId = $this->sessionManager->getSessionId();
            $lockStatus = $this->quoteLockService->getLockStatus($quoteId, $sessionId);
            
            return $lockStatus && 
                   $lockStatus['is_locked'] && 
                   isset($lockStatus['locked_by_type']) && 
                   $lockStatus['locked_by_type'] === 'admin';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get lock status for a quote
     *
     * @param int $quoteId
     * @return array
     */
    public function getLockStatus(int $quoteId): array
    {
        try {
            $sessionId = $this->sessionManager->getSessionId();
            return $this->quoteLockService->getLockStatus($quoteId, $sessionId);
        } catch (\Exception $e) {
            return ['is_locked' => false];
        }
    }

    /**
     * Check if quote is locked by customer
     * This method handles the conversion from Magento quote ID to Amasty quote ID
     *
     * @param int|null $magentoQuoteId Optional Magento quote ID. If not provided, tries to get from request
     * @return bool
     */
    public function isLockedByCustomer(?int $magentoQuoteId = null): bool
    {
        $amastyQuoteId = null;
        
        $requestQuoteId = (int)$this->request->getParam('quote_id');
        if ($requestQuoteId) {
            $amastyQuoteId = $requestQuoteId;
        }
        
        if (!$amastyQuoteId && $magentoQuoteId) {
            try {
                $connection = $this->resourceConnection->getConnection();
                $amastyQuoteTable = $this->resourceConnection->getTableName('amasty_quote');
                $select = $connection->select()
                    ->from($amastyQuoteTable, ['entity_id'])
                    ->where('quote_id = ?', $magentoQuoteId)
                    ->limit(1);
                $amastyQuoteId = (int)$connection->fetchOne($select);
            } catch (\Exception $e) {
            }
        }
        
        if (!$amastyQuoteId || $amastyQuoteId <= 0) {
            return false;
        }
        
        try {
            $collection = $this->quoteLockCollectionFactory->create();
            $collection->addFieldToFilter('quote_id', $amastyQuoteId);
            $collection->addFieldToFilter('locked_by_type', 'customer');
            $collection->load();
            
            foreach ($collection as $lock) {
                $lastActivityTimestamp = strtotime($lock->getLastActivity());
                $now = time();
                $lockTimeout = $this->quoteLockService->getLockTimeout();
                
                if (($now - $lastActivityTimestamp) <= $lockTimeout) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // Silent fail
        }

        return false;
    }

    /**
     * Check if any items in the quote have price changes
     * Compares current calculated prices with original prices from pdp_line_item
     *
     * @param \Amasty\RequestQuote\Api\Data\QuoteInterface|null $quote Quote object or null to use current quote
     * @return bool True if any items have price changes, false otherwise
     */
    public function hasQuotePriceChanges($quote = null): bool
    {
        try {
            if ($quote === null) {
                $quote = $this->getCurrentQuote();
            }

            if (!$quote) {
                return false;
            }

            $quote->getItemsCollection()->load();
            $items = $quote->getAllVisibleItems();

            if (empty($items)) {
                return false;
            }

            foreach ($items as $item) {
                try {
                    if (!$item->getProductId()) {
                        continue;
                    }

                    $priceData = $this->getCalculatedPriceForItem($item, $quote, true);

                    if ($priceData && isset($priceData['price_changed']) && $priceData['price_changed']) {
                        return true;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error checking quote price changes',
                ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return false;
        }
    }

    /**
     * Recalculate product prices for all items in a quote using latest product prices
     * This method should be called when a quote is reopened after some time
     * to ensure prices are up-to-date with current product pricing
     *
     * @param \Amasty\RequestQuote\Api\Data\QuoteInterface|null $quote Quote object or null to use current quote
     * @param bool $saveQuote Whether to save the quote after recalculation (default: false)
     * @return array Result array with 'success' status and 'updated_items' count
     */
    public function recalculateQuotePrices($quote = null, bool $saveQuote = false): array
    {
        $result = [
            'success' => false,
            'updated_items' => 0,
            'errors' => []
        ];

        try {
            if ($quote === null) {
                $quote = $this->getCurrentQuote();
            }

            if (!$quote) {
                $result['errors'][] = 'Quote not found';
                return $result;
            }

            $quote->getItemsCollection()->load();
            $items = $quote->getAllVisibleItems();

            if (empty($items)) {
                $result['success'] = true;
                return $result;
            }

            $discount = (float)($quote->getDiscount() ?? 0);
            $surcharge = (float)($quote->getSurcharge() ?? 0);
            $updatedCount = 0;

            foreach ($items as $item) {
                try {
                    if (!$item->getProductId()) {
                        continue;
                    }

                    if ($item->getParentItemId()) {
                        continue;
                    }

                    $product = null;
                    try {
                        $product = $this->productRepository->get($item->getSku());
                    } catch (NoSuchEntityException $e) {
                        $this->logger->warning(
                            'Product not found for quote item',
                            ['sku' => $item->getSku(), 'item_id' => $item->getId()]
                        );
                        continue;
                    }

                    $buyRequest = $item->getBuyRequest();
                    $buyRequestData = $buyRequest ? $buyRequest->getData() : [];

                    $pdpLineItemData = [];
                    $pdpLineItemJson = $item->getData('pdp_line_item');
                    if ($pdpLineItemJson) {
                        $pdpLineItemData = json_decode($pdpLineItemJson, true) ?: [];
                    }

                    $calculationParams = array_merge($buyRequestData, $pdpLineItemData);
                    $calculatedPrice = $this->calculateItemPrice($item, $product, $calculationParams);

                    if ($calculatedPrice !== null && $calculatedPrice > 0) {
                        $newPrice = $this->applyDiscountAndSurcharge($calculatedPrice, $discount, $surcharge);

                        $this->setItemPrice($item, $newPrice);

                        if (!empty($pdpLineItemData)) {
                            if (!isset($pdpLineItemData['OriginalUnitPrice']) && isset($pdpLineItemData['UnitPrice'])) {
                                $pdpLineItemData['OriginalUnitPrice'] = $pdpLineItemData['UnitPrice'];
                            }
                            
                            $pdpLineItemData['UnitPrice'] = $this->formatPrice((float)$calculatedPrice);
                            $qty = (float)($calculationParams['qty'] ?? $item->getQty() ?? 1);
                            $pdpLineItemData['LineItemPrice'] = $this->formatPrice((float)$calculatedPrice * $qty);

                            $pricePerSqftData = $this->advanceSearchData->getCalculatedPrice($product->getId());
                            if (isset($pricePerSqftData['price'])) {
                                $pdpLineItemData['PricePerSqft'] = $this->formatPrice((float)$pricePerSqftData['price']);
                            }

                            $productFinalPrice = (float)$product->getFinalPrice(1);
                            if ($productFinalPrice > 0) {
                                $formattedPrice = $this->pricingHelper->currency($productFinalPrice, true, false);
                                $pdpLineItemData['PricePerLinearFoot'] = $formattedPrice . ' /linear foot';
                            }

                            $pdpLineItemJson = json_encode($pdpLineItemData);
                            $item->setData('pdp_line_item', $pdpLineItemJson);

                            if ($item->getProductType() == 'configurable') {
                                $children = $item->getChildren();
                                foreach ($children as $childItem) {
                                    $childItem->setData('pdp_line_item', $pdpLineItemJson);
                                }
                            }
                        }

                        $updatedCount++;
                    }
                } catch (\Exception $e) {
                    $this->logger->error(
                        'Error recalculating price for quote item',
                        [
                            'item_id' => $item->getId(),
                            'sku' => $item->getSku(),
                            'error' => $e->getMessage()
                        ]
                    );
                    $result['errors'][] = sprintf(
                        'Item %s: %s',
                        $item->getSku(),
                        $e->getMessage()
                    );
                }
            }

            if ($updatedCount > 0) {
                try {
                    $quote->setTotalsCollectedFlag(false);
                    $quote->collectTotals();
                } catch (\Exception $e) {
                    $this->logger->error(
                        'Error recalculating quote totals',
                        ['quote_id' => $quote->getId(), 'error' => $e->getMessage()]
                    );
                    $result['errors'][] = 'Failed to recalculate totals: ' . $e->getMessage();
                }
            }

            if ($saveQuote && $updatedCount > 0) {
                try {
                    $this->quoteRepository->save($quote);
                    $quoteId = (int)$quote->getId();
                    $quote = $this->reloadQuote($quoteId);
                } catch (\Exception $e) {
                    $this->logger->error(
                        'Error saving quote after price recalculation',
                        ['quote_id' => $quote->getId(), 'error' => $e->getMessage()]
                    );
                    $result['errors'][] = 'Failed to save quote: ' . $e->getMessage();
                }
            }

            $result['success'] = true;
            $result['updated_items'] = $updatedCount;

        } catch (\Exception $e) {
            $this->logger->error(
                'Error in recalculateQuotePrices',
                ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Get calculated price for a quote item (for display in templates)
     * This method calculates the current price including discount/surcharge if applicable
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $item Quote item
     * @param \Amasty\RequestQuote\Api\Data\QuoteInterface|null $quote Optional quote object
     * @param bool $applyDiscount Whether to apply quote discount/surcharge (default: true)
     * @return array Array with 'price' (float), 'price_before_discount' (float), 'calculated_price' (float), 'old_price' (float), 'old_price_after_discount' (float), 'old_calculated_price' (float), 'price_changed' (bool), and 'price_difference' (float) keys, or null on error
     */
    public function getCalculatedPriceForItem($item, $quote = null, bool $applyDiscount = true): ?array
    {
        try {
            if ($quote === null) {
                $quote = $this->getCurrentQuote();
            }

            $product = $this->productRepository->get($item->getSku());
            $buyRequest = $item->getBuyRequest();
            $buyRequestData = $buyRequest ? $buyRequest->getData() : [];

            $pdpLineItemData = [];
            $pdpLineItemJson = $item->getData('pdp_line_item');
            if ($pdpLineItemJson) {
                $pdpLineItemData = json_decode($pdpLineItemJson, true) ?: [];
            }

            $oldCalculatedPrice = null;
            if (!empty($pdpLineItemData)) {
                if (isset($pdpLineItemData['OriginalUnitPrice'])) {
                    $oldCalculatedPrice = (float)$pdpLineItemData['OriginalUnitPrice'];
                } elseif (isset($pdpLineItemData['UnitPrice'])) {
                    $oldCalculatedPrice = (float)$pdpLineItemData['UnitPrice'];
                }
            }

            $calculationParams = array_merge($buyRequestData, $pdpLineItemData);
            $calculatedPriceData = $this->advanceSearchData->getCalculatedPrice($product->getId());
            $pricePerSqft = isset($calculatedPriceData['price']) ? (float)$calculatedPriceData['price'] : null;
            $calculatedPrice = $this->calculateItemPrice($item, $product, $calculationParams);

            if ($calculatedPrice === null || $calculatedPrice <= 0) {
                return null;
            }

            $priceBeforeDiscount = round((float)$calculatedPrice, 2);
            $finalPrice = $calculatedPrice;

            $discount = 0;
            $surcharge = 0;
            if ($applyDiscount && $quote) {
                $discount = (float)($quote->getDiscount() ?? 0);
                $surcharge = (float)($quote->getSurcharge() ?? 0);
                $finalPrice = $this->applyDiscountAndSurcharge($finalPrice, $discount, $surcharge);
            }

            $oldPriceAfterDiscount = null;
            if ($oldCalculatedPrice !== null && $oldCalculatedPrice > 0) {
                $oldPriceAfterDiscount = $this->applyDiscountAndSurcharge($oldCalculatedPrice, $discount, $surcharge);
            }
            
            $oldCalculatedPricePerSqft = null;
            if ($oldCalculatedPrice !== null && $oldCalculatedPrice > 0) {
                $calculatorType = $product->getAttributeText('incstores_pim_calculator_type');
                $directPriceTypes = ['case', 'none', 'pre_cut_roll'];
                $productFloorType = $calculationParams['product_floor_type'] ?? '';
                $cbcShopBy = $calculationParams['CBCShopBy'] ?? '';
                $isCbcProduct = ($productFloorType === 'CBC Tiles' || !empty($cbcShopBy));
                
                if (in_array($calculatorType, $directPriceTypes) || $isCbcProduct) {
                    if ($calculatorType === 'case' || $calculatorType === 'pre_cut_roll') {
                        $coverageArea = (float)$product->getIncstoresPimCoverage();
                        if ($coverageArea > 0) {
                            $oldCalculatedPricePerSqft = round($oldCalculatedPrice / $coverageArea, 2);
                        }
                    } elseif ($isCbcProduct) {
                        if (isset($calculationParams['PricePerSqft']) && (float)$calculationParams['PricePerSqft'] > 0) {
                            $oldCalculatedPricePerSqft = (float)$calculationParams['PricePerSqft'];
                        } else {
                            $calculatedPriceData = $this->advanceSearchData->getCalculatedPrice($product->getId());
                            $oldCalculatedPricePerSqft = isset($calculatedPriceData['price']) 
                                ? (float)$calculatedPriceData['price'] 
                                : $oldCalculatedPrice;
                        }
                    } else {
                        $oldCalculatedPricePerSqft = $oldCalculatedPrice;
                    }
                } else {
                    $roomWidth = $calculationParams['RoomWidth'] ?? $calculationParams['CustomerRoomWidth'] ?? null;
                    $roomLength = $calculationParams['RoomLength'] ?? $calculationParams['CustomerRoomLength'] ?? null;
                    
                    if ($roomWidth && $roomLength && (float)$roomWidth > 0 && (float)$roomLength > 0) {
                        $area = (float)$roomWidth * (float)$roomLength;
                        if ($area > 0) {
                            $oldCalculatedPricePerSqft = round($oldCalculatedPrice / $area, 2);
                        }
                    } else {
                        $tileWidthInches = (float)$product->getData('incstores_pim_exact_width_inches');
                        $tileLengthInches = (float)$product->getData('incstores_pim_exact_length_inches');
                        
                        if ($tileWidthInches > 0 && $tileLengthInches > 0) {
                            $tileWidthFeet = $tileWidthInches / 12;
                            $tileLengthFeet = $tileLengthInches / 12;
                            $sqftPerTile = $tileWidthFeet * $tileLengthFeet;
                            
                            if ($sqftPerTile > 0) {
                                $oldCalculatedPricePerSqft = round($oldCalculatedPrice / $sqftPerTile, 2);
                            }
                        }
                    }
                }
            }

            $result = [
                'price' => $finalPrice,
                'price_before_discount' => $priceBeforeDiscount
            ];
            
            if ($pricePerSqft !== null) {
                $result['calculated_price'] = round($pricePerSqft, 2);
            }
            
            if ($oldCalculatedPrice !== null && $oldCalculatedPrice > 0) {
                $result['old_price'] = round($oldCalculatedPrice, 2);
                
                if ($oldPriceAfterDiscount !== null) {
                    $result['old_price_after_discount'] = $oldPriceAfterDiscount;
                }
                
                if ($oldCalculatedPricePerSqft !== null) {
                    $result['old_calculated_price'] = $oldCalculatedPricePerSqft;
                }
                
                $result['price_changed'] = false;
                $result['price_difference'] = 0;
                
                if (isset($result['calculated_price']) && isset($result['old_calculated_price'])) {
                    $priceDifference = abs($result['calculated_price'] - $result['old_calculated_price']);
                    $threshold = 0.10;
                    if ($priceDifference >= $threshold) {
                        $result['price_changed'] = true;
                        $result['price_difference'] = round($priceDifference, 2);
                    }
                } elseif (isset($result['calculated_price']) && isset($result['old_price']) && $oldCalculatedPrice > 0) {
                    $roomWidth = $calculationParams['RoomWidth'] ?? $calculationParams['CustomerRoomWidth'] ?? null;
                    $roomLength = $calculationParams['RoomLength'] ?? $calculationParams['CustomerRoomLength'] ?? null;
                    
                    if ($roomWidth && $roomLength && (float)$roomWidth > 0 && (float)$roomLength > 0) {
                        $area = (float)$roomWidth * (float)$roomLength;
                        if ($area > 0) {
                            $oldPricePerSqft = $oldCalculatedPrice / $area;
                            $priceDifference = abs($result['calculated_price'] - $oldPricePerSqft);
                            $threshold = 0.10;
                            if ($priceDifference >= $threshold) {
                                $result['price_changed'] = true;
                                $result['price_difference'] = round($priceDifference, 2);
                            }
                        }
                    }
                }
            }
            
            return $result;

        } catch (\Exception $e) {
            $this->logger->error(
                'Error getting calculated price for item',
                [
                    'item_id' => $item->getId(),
                    'sku' => $item->getSku(),
                    'error' => $e->getMessage()
                ]
            );
            return null;
        }
    }

    /**
     * Calculate the price for a quote item based on product and calculation parameters
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $item Quote item
     * @param \Magento\Catalog\Api\Data\ProductInterface $product Product object
     * @param array $params Calculation parameters (from buy request or pdp_line_item)
     * @return float|null Calculated price or null if calculation failed
     */
    private function calculateItemPrice($item, $product, array $params): ?float
    {
        try {
            $productFloorType = $params['product_floor_type'] ?? '';
            $cbcShopBy = $params['CBCShopBy'] ?? '';

            if ($productFloorType === 'CBC Tiles' || !empty($cbcShopBy)) {
                $finalPrice = $product->getFinalPrice(1);
                if ($finalPrice > 0) {
                    return round((float)$finalPrice, 2);
                }
                $pricePerTile = $params['price_per_tile'] ?? null;
                if ($pricePerTile !== null) {
                    return round((float)$pricePerTile, 2);
                }
            }

            if ($productFloorType === 'Trailer Rolls') {
                $trailerRollsPrice = $params['trailer_rolls_price'] ?? null;
                if ($trailerRollsPrice !== null) {
                    return round((float)$trailerRollsPrice, 2);
                }
            }

            $getCalculatedPrice = $this->advanceSearchData->getCalculatedPrice($product->getId());
            $calculatorType = $product->getAttributeText('incstores_pim_calculator_type');
            $directPriceTypes = ['case', 'none', 'pre_cut_roll'];
            if (in_array($calculatorType, $directPriceTypes)) {
                $finalPrice = $product->getFinalPrice(1);
                if ($finalPrice > 0) {
                    return round((float)$finalPrice, 2);
                }
            }

            $hasLength = isset($params['Length']) && (float)$params['Length'] > 0;
            $hasWidth = isset($params['Width']) && (float)$params['Width'] > 0;
            $hasRoomWidth = isset($params['RoomWidth']) || isset($params['CustomerRoomWidth']);
            $hasRoomLength = isset($params['RoomLength']) || isset($params['CustomerRoomLength']);
            
            if ($hasLength && $hasWidth && !$hasRoomWidth && !$hasRoomLength && 
                $calculatorType !== 'case' &&
                isset($getCalculatedPrice['price'])) {
                $tileWidthInches = (float)$product->getData('incstores_pim_exact_width_inches');
                $tileLengthInches = (float)$product->getData('incstores_pim_exact_length_inches');
                
                if ($tileWidthInches > 0 && $tileLengthInches > 0) {
                    $tileWidthFeet = $tileWidthInches / 12;
                    $tileLengthFeet = $tileLengthInches / 12;
                    $sqftPerTile = $tileWidthFeet * $tileLengthFeet;
                    $pricePerSqft = (float)$getCalculatedPrice['price'];
                    $unitPrice = $pricePerSqft * $sqftPerTile;
                    return round($unitPrice, 2);
                }
            }

            $roomWidth = $params['RoomWidth'] ?? $params['CustomerRoomWidth'] ?? null;
            $roomLength = $params['RoomLength'] ?? $params['CustomerRoomLength'] ?? null;

            if ($roomWidth && $roomLength && 
                isset($getCalculatedPrice['price']) && 
                (float)$roomWidth > 0 && (float)$roomLength > 0) {
                $customPrice = (float)$roomWidth * (float)$roomLength * (float)$getCalculatedPrice['price'];
                return round($customPrice, 2);
            }

            if (isset($params['custom_price']) && !empty($params['custom_price'])) {
                return round((float)$params['custom_price'], 2);
            }

            $finalPrice = $product->getFinalPrice(1);
            if ($finalPrice > 0) {
                return round((float)$finalPrice, 2);
            }

            $currentCustomPrice = $item->getCustomPrice();
            if ($currentCustomPrice && $currentCustomPrice > 0) {
                return round((float)$currentCustomPrice, 2);
            }

        } catch (\Exception $e) {
            $this->logger->error(
                'Error calculating item price',
                [
                    'item_id' => $item->getId(),
                    'product_id' => $product->getId(),
                    'error' => $e->getMessage()
                ]
            );
        }

        return null;
    }

    /**
     * Set price on quote item (custom price, base price, etc.)
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $item
     * @param float $price
     * @return void
     */
    private function setItemPrice($item, float $price): void
    {
        $item->setCustomPrice($price);
        $item->setOriginalCustomPrice($price);
        $item->setPrice($price);
        $item->setBasePrice($price);
        $item->getProduct()->setIsSuperMode(true);
    }

    /**
     * Apply discount and surcharge to a price
     *
     * @param float $price
     * @param float $discount
     * @param float $surcharge
     * @return float
     */
    private function applyDiscountAndSurcharge(float $price, float $discount, float $surcharge): float
    {
        if ($discount > 0) {
            $price = $price * (1 - ($discount / 100));
        }
        
        if ($surcharge > 0) {
            $price = $price * (1 + ($surcharge / 100));
        }
        
        return round($price, 2);
    }

    /**
     * Format price to 2 decimal places
     *
     * @param float $price
     * @return string
     */
    private function formatPrice(float $price): string
    {
        return number_format($price, 2, '.', '');
    }
}

