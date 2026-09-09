<?php
declare(strict_types=1);

namespace Dcw\ShoppingCart\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteRepository;
use Magento\Catalog\Model\ProductFactory;
use Dcw\FlooringCalculation\Helper\Data;
use Dcw\AdvanceSearch\ViewModel\Data as AdvanceSearchData;
use Dcw\FlooringCalculation\Helper\Data as FlooringCalculationHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Dcw\ShoppingCart\ViewModel\Data as ShoppingCartData;
use Dcw\FlooringCalculation\ViewModel\Data as FlooringCalculationViewModel;
use Dcw\ShoppingCart\Model\CustomPriceSanitizer;
use Dcw\ShoppingCart\Model\CustomPriceDebugLogger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\ItemFactory as QuoteItemFactory;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Framework\Message\ManagerInterface;
use Laminas\Stdlib\Parameters;

class CheckoutCartAddObserver implements ObserverInterface
{
    protected $request;
    private $serializer;
	protected $_productloader;
	protected $dataHelper;
	protected $_cart;
	protected $checkoutSession;
	protected $quoteRepository;
	protected $advanceSearchData;
	protected $flooringCalculationHelper;
	protected $productRepository;
	protected $shoppingCartData;
	protected $flooringCalculationViewModel;
    private CustomPriceSanitizer $customPriceSanitizer;
    private CustomPriceDebugLogger $customPriceDebugLogger;
    private QuoteItemFactory $quoteItemFactory;
    private ManagerInterface $messageManager;

    public function __construct(
        RequestInterface $request,
        SerializerInterface $serializer,
		ProductFactory $_productloader,
		Data $dataHelper,
		Cart $cart,
		CheckoutSession $checkoutSession,
		QuoteRepository $quoteRepository,
		AdvanceSearchData $advanceSearchData,
		FlooringCalculationHelper $flooringCalculationHelper,
		ProductRepositoryInterface $productRepository,
		ShoppingCartData $shoppingCartData,
		FlooringCalculationViewModel $flooringCalculationViewModel,
        CustomPriceSanitizer $customPriceSanitizer,
        CustomPriceDebugLogger $customPriceDebugLogger,
        QuoteItemFactory $quoteItemFactory,
        ManagerInterface $messageManager
    )
    {
        $this->request = $request;
        $this->serializer = $serializer;
		$this->_productloader = $_productloader;
		$this->dataHelper = $dataHelper;
		$this->_cart = $cart;
		$this->checkoutSession = $checkoutSession;
		$this->quoteRepository = $quoteRepository;
		$this->advanceSearchData = $advanceSearchData;
		$this->flooringCalculationHelper = $flooringCalculationHelper;
		$this->productRepository = $productRepository;
		$this->shoppingCartData = $shoppingCartData;
		$this->flooringCalculationViewModel = $flooringCalculationViewModel;
        $this->customPriceSanitizer = $customPriceSanitizer;
        $this->customPriceDebugLogger = $customPriceDebugLogger;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->messageManager = $messageManager;
    }

    public function execute(EventObserver $observer)
    {
		$item = $this->resolveQuoteItemFromEvent($observer);
		if ($item === null) {
			$this->messageManager->addErrorMessage(
				__(
					'We couldn\'t apply custom options to your cart line. '
					. 'Please open the product page and add the item to your cart again.'
				)
			);

			return;
		}
		$product = $observer->getProduct();
		$post = $this->ensurePostArray($this->request->getPost());
		$initialRequestPost = $post;
		$isReOrder = true;
		$pdplinedata = [];

		try {
			$childProduct = $this->productRepository->get($item->getSku());
            if ($childProduct) {
                $requestedQty = 0;
                $isRollProduct = false;

                if (isset($post['RoomWidth']) && isset($post['RoomLength']) && $post['RoomWidth'] > 0 && $post['RoomLength'] > 0 && $post['RollType'] == 'Recommended Roll Length') {
                    $requestedQty = (float)$post['RoomLength'] * (float)($post['qty'] ?? $item->getQty());
                    $isRollProduct = true;
                }

                if (isset($post['custom_length']) && $post['custom_length'] > 0 && $post['RollType'] == 'Custom Roll Length') {
                    $requestedQty = (float)$post['custom_length'] * (float)($post['qty'] ?? $item->getQty());
                    $isRollProduct = true;
                }

                if ($isRollProduct) {
                    $getMiniCartQty = $this->flooringCalculationViewModel->getMiniCartQty($childProduct->getSku());
                    $requestedQty = (float)$post['RoomLength'] * (float)($post['qty'] ?? $item->getQty());
                    $totalRequestedQty = $getMiniCartQty + $requestedQty;
                    $isProductCanAddToCart = $this->flooringCalculationViewModel->isProductCanAddToCart($childProduct->getId(), $totalRequestedQty);
                    $availableQty = $this->flooringCalculationViewModel->getProductAvailableQty($childProduct->getId());
                    
                    if (!$isProductCanAddToCart) {
                        throw new LocalizedException(__('You can\'t order more than %1 linear feet. Requested: %2 linear feet.', $availableQty, $totalRequestedQty));
                    }
                }
            }
			$checkForPromiseDate = $this->shoppingCartData->getProductPromiseDateFromProductObject($childProduct, $item->getQty());
		} catch (NoSuchEntityException $e) {
			//do nothing
		}

		if ((isset($post['pids']) && isset($post['colors'])) || isset($post['selected_option_child_id']) || isset($post['product'])) {
			$isReOrder = false;
		}

		if ($isReOrder) {
			$buyRequest = $item->getBuyRequest();
            $post = $this->ensurePostArray($buyRequest->getData());
			$this->mergePdpLineItemIntoPost($observer, $item, $post);
			// Use simple/child product id (from $item->getSku()) — parent configurable id can return wrong AdvanceSearch price.
			$calculatedPriceProductId = (isset($childProduct) && $childProduct->getId())
				? (int) $childProduct->getId()
				: (int) $item->getProduct()->getId();

			$roomAreaCustomPriceApplied = isset($childProduct)
				? $this->applyRoomAreaCustomPriceIfApplicable($item, $post, $childProduct)
				: false;

			try {
				$shipToText = $this->flooringCalculationHelper->getExpectedShipDate($childProduct);
			} catch (NoSuchEntityException $e) {
				//do nothing
			}

			$itemSku = $item->getSku();
			if ($itemSku) {
				$cbcType = $mainProductSku = $mainProduct = "";
				$parts = explode('_', $itemSku);
				if (!empty($parts)) {
					$cbcType = end($parts);
					$mainProductSku = $parts[0] ?? '';
					if ($mainProductSku) {
						$mainProduct = $this->productRepository->get($mainProductSku);
					}
				}

				if (in_array($cbcType, ['center', 'border', 'corner'], true) && $mainProduct) {
					$cbcAdditionalOptions = [];

					$cbcAdditionalOptions['flooring_color'] = [
						'label' => 'Color',
						'value' => $childProduct->getAttributeText('incstores_pim_color_axis')
					];
					
					$cbcAdditionalOptions['cbc_type'] = [
						'label' => 'CBC Type',
						'value' => ucfirst($cbcType)
					];
					$cbcAdditionalOptions['configurable_product_url'] = [
						'label' => 'Configurable Product Url',
						'value' => $mainProduct->getProductUrl()
					];
					
					if (!empty($cbcAdditionalOptions)) {
						$item->addOption([
							'product_id' => $item->getProductId(),
							'code' => 'additional_options',
							'value' => $this->serializer->serialize($cbcAdditionalOptions)
						]);
					}
				}
				// Do not overwrite room-area total (width × length × $/sqft) with catalog final price.
				if (!$roomAreaCustomPriceApplied) {
					$post['custom_price'] = $childProduct->getFinalPrice(1);
				}
				$post['qty'] = $item->getQty();
			}

			$checkForQtyBackorder = $this->shoppingCartData->checkForQtyBackorder($childProduct, $item->getQty());

			if ($checkForQtyBackorder) {
				$shipToText = "";
			}
	
			if (!isset($post['ship_to_text'])) {
				$post['ship_to_text'] = $shipToText;
			}

			if (!isset($post['pricePerSqft'])) {
				$getCalculatedPrice = $this->advanceSearchData->getCalculatedPrice($calculatedPriceProductId);
				if (isset($getCalculatedPrice['price'])) {
					$pdplinedata['PricePerSqft']= number_format((float)$getCalculatedPrice['price'], 2, '.', '') ?? 0;
				}
			}
		}

		// PDP / normal add-to-cart skips the $isReOrder block above, so room × $/sqft is never set unless we apply here.
		if (!$isReOrder && isset($childProduct)) {
			$this->applyRoomAreaCustomPriceIfApplicable($item, $post, $childProduct);
		}

		$this->applyFlooringCustomPriceFromRequest($item, $post);

		$this->logCartAddPostDiagnosticsIfRelevant($item, $initialRequestPost, $post, $isReOrder);

		// When adding from related products popup (minimal post), populate missing values for case/none products
		$productForShipping = null;
		try {
			$productForShipping = isset($childProduct) ? $childProduct : $this->productRepository->get($item->getSku());
		} catch (NoSuchEntityException $e) {
			$productForShipping = $item->getProduct();
		}

		if ($productForShipping) {
			$calcType = $productForShipping->getAttributeText('incstores_pim_calculator_type');
			$isCaseOrNone = in_array(strtolower((string)$calcType), ['case', 'none'], true);

			if ($isCaseOrNone && !isset($post['selected_option_child_id']) && isset($post['product'])) {
				// Add missing values for case/none when adding from popup
				if (!isset($post['ship_to_text'])) {
					try {
						$shipToText = $this->flooringCalculationHelper->getExpectedShipDate($productForShipping);
						if ($this->shoppingCartData->checkForQtyBackorder($productForShipping, $post['qty'] ?? $item->getQty())) {
							$shipToText = "";
						}
						$post['ship_to_text'] = $shipToText;
					} catch (\Exception $e) {
						$post['ship_to_text'] = "";
					}
				}
				if (!isset($post['custom_price']) && $productForShipping->getFinalPrice(1) > 0) {
					$post['custom_price'] = $productForShipping->getFinalPrice(1);
				}
				if (!isset($post['selected_option_child_id'])) {
					$post['selected_option_child_id'] = $productForShipping->getId();
				}
			}
			if (!isset($childProduct) && $productForShipping) {
				$childProduct = $productForShipping;
			}
			if (!isset($post['ship_to_text']) && !$isCaseOrNone) {
				// For other product types, still calculate shipping estimate
				try {
					$shipToText = $this->flooringCalculationHelper->getExpectedShipDate($productForShipping);
					if ($this->shoppingCartData->checkForQtyBackorder($productForShipping, $item->getQty())) {
						$shipToText = "";
					}
					$post['ship_to_text'] = $shipToText;
				} catch (\Exception $e) {
					$post['ship_to_text'] = "";
				}
			}
		}
		
		$calculatorType = '';

		if (isset($post['selected_option_child_id']) && $post['selected_option_child_id']!='') {
			$simpleProd = $childProduct; //$this->_productloader->create()->load($post['selected_option_child_id']);
			$calculatorType = $simpleProd->getAttributeText('incstores_pim_calculator_type');
		}
		
		$cart = $this->checkoutSession->getQuote();
		$cartitems = $cart->getAllItems();
		$qty = $observer->getProduct()->getQty();

		if(!empty($cartitems && $calculatorType!='roll')) {
			foreach ($cart->getAllItems() as $cartLoopItem) {
				if ($cartLoopItem->getProduct()->getTypeId() == 'configurable' && $cartLoopItem->getProduct()->getId() == $product->getId()) {
					//$cartLoopItem->setQty($post['qty']);
					//$cart->save();
				}
			}
			$post['RoomLength'] = "";
			$post['RollType'] = "";
			$post['RollSize'] = "";
			$post['custom_length'] = "";
		}
			
		if ($additionalOption = $item->getOptionByCode('additional_options')) {
			$additionalOptions = $this->serializer->unserialize($additionalOption->getValue());
		}

        $selectedRollType = "";
		
		if(isset($post['RoomWidth']) && isset($post['RoomLength']) && $post['RoomWidth'] > 0 && $post['RoomLength'] > 0 && $post['RollType'] == 'Recommended Roll Length'){
			$additionalOptions['room_width'] = [
				'label' => 'Roll Width',
				'value' => $post['RoomWidth']
			];
			$additionalOptions['room_length'] = [
				'label' => 'Roll Length',
				'value' => $post['RoomLength']
			];
			$selectedRollType = "Recommended";
		} else {
			if(isset($post['custom_length']) && $post['custom_length'] > 0 && $post['RollType'] == 'Custom Roll Length'){
				$additionalOptions['custom_length'] = [
					'label' => 'Custom Length',
					'value' => $post['custom_length']
				];  
			}
			$selectedRollType = "Custom";
		}

        if ($selectedRollType == "Recommended" && $childProduct) {
            
            $getMiniCartQty = $this->flooringCalculationViewModel->getMiniCartQty($childProduct->getSku());
            $requestedQty = (float)$post['RoomLength'] * (float)($post['qty'] ?? $item->getQty());
            $totalRequestedQty = $getMiniCartQty + $requestedQty;
            $isProductCanAddToCart = $this->flooringCalculationViewModel->isProductCanAddToCart($childProduct->getId(), $totalRequestedQty);
            $availableQty = $this->flooringCalculationViewModel->getProductAvailableQty($childProduct->getId());
            
            if (!$isProductCanAddToCart) {
                throw new LocalizedException(__('You can\'t order more than %1 linear feet. Requested: %2 linear feet.', $availableQty, $totalRequestedQty));
            }
        }

        if ($selectedRollType == "Custom" && $childProduct) {
            $getMiniCartQty = $this->flooringCalculationViewModel->getMiniCartQty($childProduct->getSku());
            $requestedQty = (float)$post['custom_length'] * (float)($post['qty'] ?? $item->getQty());
            $totalRequestedQty = $getMiniCartQty + $requestedQty;
            $isProductCanAddToCart = $this->flooringCalculationViewModel->isProductCanAddToCart($childProduct->getId(), $totalRequestedQty);
            $availableQty = $this->flooringCalculationViewModel->getProductAvailableQty($childProduct->getId());

            if (!$isProductCanAddToCart) {
                throw new LocalizedException(__('You can\'t order more than %1 linear feet. Requested: %2 linear feet.', $availableQty, $totalRequestedQty));
            }
        }


		if (!empty($additionalOptions)) {
			$item->addOption([
				'product_id' => $item->getProductId(),
				'code' => 'additional_options',
				'value' => $this->serializer->serialize($additionalOptions)
			]); 
		}

		if (isset($post['pricePerSqft'])) {
			$pdplinedata['PricePerSqft']= number_format((float)$post['pricePerSqft'], 2, '.', '') ?? 0;
		}

		if (isset($post['qty'])) {
			$pdplinedata['LineItemUnitQuantity']= $post['qty'] ?? 0;
		}

		if (isset($post['overage_percent'])) {
			$pdplinedata['OveragePercent']= $post['overage_percent'] ?? 0;
		}

		if (isset($post['custom_price']) && (float) $post['custom_price'] <= 0 && isset($childProduct)) {
			if ($childProduct->getFinalPrice(1) > 0) {
				$post['custom_price'] = $childProduct->getFinalPrice(1);
				$priceTargetItem = $item->getParentItem() ?: $item;
				$priceTargetItem->setCustomPrice((float) $post['custom_price']);
				$priceTargetItem->setOriginalCustomPrice((float) $post['custom_price']);
				$priceTargetItem->getProduct()->setIsSuperMode(true);
			}
		}

		$this->logZeroOrNegativeCustomPriceContext($item, $post, $isReOrder, $childProduct ?? null);

		if (isset($post['custom_price'])) {
			$pdplinedata['UnitPrice']= $post['custom_price'] ?? 0;
		}

		if(isset($post['custom_price'])){
			$pdplinedata['LineItemPrice']= ((float)$post['custom_price']*(int)($post['qty'] ?? $item->getQty())) ?? 0;
		}

		if(isset($post['tileSize'])){
			$pdplinedata['TileSize']= $post['tileSize'] ?? 0;
		}
		
		if(isset($post['covers'])){
			$pdplinedata['Covers']= $post['covers'] ?? 0;
		}
		
		if(isset($post['squareFootage'])){
			$pdplinedata['SquareFootage']= $post['squareFootage'] ?? 0;
		}
		
		if(isset($post['length'])){
			$pdplinedata['Length']= $post['length'] ?? 0;
			$pdplinedata['CustomerLength']= $post['length'] ?? 0;
		}

		if(isset($post['width']) ){
			$pdplinedata['Width']= $post['width'] ?? 0;
			$pdplinedata['CustomerWidth']= $post['width'] ?? 0;
		}

		if(isset($post['PricePerLinearFoot'])){
			$pdplinedata['PricePerLinearFoot']= $post['PricePerLinearFoot'] ?? 0;
		}

		if(isset($post['RoomWidth'])){
			$pdplinedata['RoomWidth']= $post['RoomWidth'] ?? 0;
		}

		if(isset($post['CustomerRoomWidth'])){
			$pdplinedata['CustomerRoomWidth']= $post['CustomerRoomWidth'] ?? 0;
		}

		if(isset($post['CustomerRoomLength'])){
			$pdplinedata['CustomerRoomLength']= $post['CustomerRoomLength'] ?? 0;
		}

		if(isset($post['CustomerRoomLengthGroup'])){
			$pdplinedata['CustomerRoomLengthGroup']= $post['CustomerRoomLengthGroup'] ?? 0;
		}

		if(isset($post['RoomLength'])){
			$pdplinedata['RoomLength']= $post['RoomLength'] ?? 0;
		}
		
		if(isset($post['RollType'])){
			$pdplinedata['RollType']= $post['RollType'] ?? 0;
		}
		
		if(isset($post['QuantityRange'])){
			$pdplinedata['QuantityRange']= $post['QuantityRange'] ?? 0;
		}
		
		if(isset($post['RollSize'])){
			$pdplinedata['RollSize']= $post['RollSize'] ?? 0;
		}
		
		if(isset($post['TrailerRollType'])){
			$pdplinedata['TrailerRollType']= $post['TrailerRollType'] ?? 0;
		}
		
		if(isset($post['CBCShopBy'])){
			$pdplinedata['CBCShopBy']= $post['CBCShopBy'] ?? 0;
		}
		
		if(isset($post['CBCTileSize'])){
			$pdplinedata['CBCTileSize']= $post['CBCTileSize'] ?? 0;
		}

		if(isset($post['CBCShopBy'])){
			$pdplinedata['CBCShopBy']= $post['CBCShopBy'] ?? 0;
		}

		if(isset($post['border_tiles_qty'])){
			$pdplinedata['BorderTileQty']= $post['border_tiles_qty'] ?? 0;
		}

		if(isset($post['center_tiles_qty'])){
			$pdplinedata['CenterTileQty']= $post['center_tiles_qty'] ?? 0;
		}

		if(isset($post['corner_tiles_qty'])){
			$pdplinedata['CornerTileQty']= $post['corner_tiles_qty'] ?? 0;
		}

		$checkForQtyBackorder = $this->shoppingCartData->checkForQtyBackorder($childProduct, $post['qty'] ?? $item->getQty());

		if(isset($post['ship_to_text']) && !$checkForQtyBackorder){
			$pdplinedata['shipping_estimate'] = $post['ship_to_text'] ?? '';
		} else {
			$pdplinedata['shipping_estimate'] = "";
		}

		if (isset($post['selected_option_child_id']) && $post['selected_option_child_id']!='') {
			$simpledata = $childProduct; //$this->_productloader->create()->load($post['selected_option_child_id']);

			if (!empty($simpledata) && $simpledata->getIncstoresPimExactLengthInches()!="") {
				$pdplinedata['CustomLength']= $simpledata->getIncstoresPimExactLengthInches();
				$pdplinedata['Length']= $simpledata->getIncstoresPimExactLengthInches();
				$pdplinedata['Width']=	$simpledata->getIncstoresPimExactWidthInches();
			} else {
				$pdplinedata['CustomLength']= $post['custom_length'];
				$pdplinedata['Width']= $simpledata->getIncstoresPimExactWidthInches();
			}

			//custom_length value issue fix
			$postCustomLength = $post['custom_length'] ?? '';
			if ($postCustomLength) {
				$pdplinedata['CustomLength']= $post['custom_length'];
			}
		}

		if ($checkForPromiseDate) {
			$pdplinedata['promise_date']= $checkForPromiseDate;
		}

		if (!empty($pdplinedata)) {
			$pdplinedata_json = json_encode($pdplinedata);
			$item->setData('pdp_line_item', $pdplinedata_json);
			
			// Get the quote item from the event
			$quoteItem = $observer->getEvent()->getQuoteItem();
			$quoteItem->setData('pdp_line_item', $pdplinedata_json);

			// Get the quote item from the event
			$quoteItem = $observer->getEvent()->getQuoteItem();
			$quoteItem->setData('pdp_line_item', $pdplinedata_json);
			
			// If it's a parent item (configurable product), get the child (simple product)
			if ($item->getProductType() == 'configurable') {
				$children = $item->getChildren();

				// Loop through children (simple products)
				foreach ($children as $childItem) {
					$childItem->setData('pdp_line_item', $pdplinedata_json);
				}
			}
		}
    }

    /**
     * info_buyRequest (getBuyRequest) does not contain PDP JSON. Merge keys from pdp_line_item
     * (quote item or, when present, sales order item) into $post so reorder matches add-to-cart shape.
     *
     * @param array<string, mixed> $post
     */
    private function mergePdpLineItemIntoPost(EventObserver $observer, AbstractItem $quoteItem, array &$post): void
    {
        $pdpJson = $quoteItem->getData('pdp_line_item');
        if ($pdpJson === null || $pdpJson === '') {
            $orderItem = $observer->getEvent()->getData('sales_order_item')
                ?? $observer->getEvent()->getData('order_item');
            if ($orderItem instanceof OrderItem) {
                $pdpJson = $orderItem->getData('pdp_line_item');
            }
        }
        if (!is_string($pdpJson) || $pdpJson === '') {
            return;
        }
        $pdp = json_decode($pdpJson, true);
        if (!is_array($pdp)) {
            return;
        }

        $fromPdp = [];
        $map = [
            'LineItemUnitQuantity' => 'qty',
            'UnitPrice' => 'custom_price',
            'RoomWidth' => 'RoomWidth',
            'RoomLength' => 'RoomLength',
            'RollType' => 'RollType',
            'RollSize' => 'RollSize',
            'shipping_estimate' => 'ship_to_text',
            'CustomLength' => 'custom_length',
            'Length' => 'length',
            'Width' => 'width',
            'PricePerLinearFoot' => 'PricePerLinearFoot',
            'CustomerRoomWidth' => 'CustomerRoomWidth',
            'CustomerRoomLength' => 'CustomerRoomLength',
            'CustomerRoomLengthGroup' => 'CustomerRoomLengthGroup',
            'TileSize' => 'tileSize',
            'SquareFootage' => 'squareFootage',
            'OveragePercent' => 'overage_percent',
        ];
        foreach ($map as $pdpKey => $postKey) {
            if (!array_key_exists($pdpKey, $pdp)) {
                continue;
            }
            $val = $pdp[$pdpKey];
            if ($val === null || $val === '') {
                continue;
            }
            $fromPdp[$postKey] = $val;
        }

        if ($fromPdp === []) {
            return;
        }

        $post = array_merge($post, $fromPdp);
    }

    /**
     * Prefer quote_item; otherwise sales_order_item / order_item (must be Order\Item).
     * If an order line is given, load the linked quote item by quote_item_id when available.
     */
    private function resolveQuoteItemFromEvent(EventObserver $observer): ?AbstractItem
    {
        $event = $observer->getEvent();
        $raw = $event->getData('quote_item')
            ?? $event->getData('sales_order_item')
            ?? $event->getData('order_item');

        if ($raw instanceof AbstractItem) {
            return $raw;
        }

        if ($raw instanceof OrderItem) {
            $quoteItemId = (int) $raw->getQuoteItemId();
            if ($quoteItemId <= 0) {
                return null;
            }
            $quoteItem = $this->quoteItemFactory->create();
            $quoteItem->load($quoteItemId);

            return $quoteItem->getId() ? $quoteItem : null;
        }

        return null;
    }

    /**
     * Logs to var/log/custom_price_debug.log when unit custom_price is still <= 0 after empty/final-price fix.
     * Captures roll/room inputs and Advance Search price for later RCA.
     */
    private function logZeroOrNegativeCustomPriceContext(
        AbstractItem $item,
        array $post,
        bool $isReOrder,
        ?Product $childProduct
    ): void {
        if (isset($post['custom_price']) && (float) $post['custom_price'] > 0) {
            return;
        }

        // Sample SKUs are zero-priced and start with S — exclude from RCA noise.
        $sku = (string) $item->getSku();
        if ($sku !== '' && strtoupper($sku[0]) == 'S') {
            return;
        }

        $productIdForCalc = $childProduct && $childProduct->getId()
            ? (int) $childProduct->getId()
            : (int) $item->getProduct()->getId();

        $advanceSearch = $this->advanceSearchData->getCalculatedPrice($productIdForCalc);

        $catalogFinal = null;
        if ($childProduct !== null) {
            $catalogFinal = (float) $childProduct->getFinalPrice(1);
        } else {
            try {
                $catalogFinal = (float) $this->productRepository->get($item->getSku())->getFinalPrice(1);
            } catch (NoSuchEntityException $e) {
                $catalogFinal = (float) $item->getProduct()->getFinalPrice(1);
            }
        }

        $payload = [
            'ts' => gmdate('c'),
            'quote_id' => $item->getQuoteId(),
            'quote_item_id' => $item->getId(),
            'sku' => $item->getSku(),
            'is_reorder' => $isReOrder,
            'post_custom_price' => $post['custom_price'] ?? null,
            'quote_item_custom_price' => $item->getCustomPrice(),
            'RoomWidth' => $post['RoomWidth'] ?? null,
            'RoomLength' => $post['RoomLength'] ?? null,
            'RollType' => $post['RollType'] ?? null,
            'custom_length' => $post['custom_length'] ?? null,
            'qty_post' => $post['qty'] ?? null,
            'qty_item' => $item->getQty(),
            'pricePerSqft' => $post['pricePerSqft'] ?? null,
            'product_floor_type' => $this->request->getParam('product_floor_type') ?? null,
            'advance_search_product_id' => $productIdForCalc,
            'advance_search_calculated' => $advanceSearch,
            'catalog_final_price' => $catalogFinal,
        ];

        $this->customPriceDebugLogger->log(
            'CheckoutCartAddObserver custom_price<=0 ' . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR)
        );
    }

    /**
     * Logs a compact view of POST / request params when pricing looks wrong (avoids logging every add).
     * Set DCW_LOG_CART_ADD_POST=1 in the environment to log every PDP add for deeper RCA.
     */
    private function logCartAddPostDiagnosticsIfRelevant(
        AbstractItem $item,
        array $initialRequestPost,
        array $postAfterPricingPipeline,
        bool $isReOrder
    ): void {
        $force = getenv('DCW_LOG_CART_ADD_POST') === '1' || getenv('DCW_LOG_CART_ADD_POST') === 'true';

        $postUnit = isset($postAfterPricingPipeline['custom_price'])
            ? (float) $postAfterPricingPipeline['custom_price']
            : null;
        $itemCustom = $item->getCustomPrice();
        $itemUnit = $itemCustom !== null && $itemCustom !== '' ? (float) $itemCustom : null;

        $looksWrong = $force
            || $postUnit === null
            || $postUnit <= 0.0
            || $itemUnit === null
            || $itemUnit <= 0.0;

        if (!$looksWrong) {
            return;
        }

        $payload = [
            'ts' => gmdate('c'),
            'event' => 'CheckoutCartAddObserver post_trace',
            'quote_id' => $item->getQuoteId(),
            'quote_item_id' => $item->getId(),
            'sku' => $item->getSku(),
            'is_reorder' => $isReOrder,
            'forced_full_log' => $force,
            'post_unit_after_pipeline' => $postUnit,
            'quote_item_custom_unit' => $itemUnit,
            'request_price_params' => $this->extractRequestPriceParamsForLog(),
            'post_initial_subset' => $this->extractCustomPriceRelatedPostKeys($initialRequestPost),
            'post_after_pricing_subset' => $this->extractCustomPriceRelatedPostKeys($postAfterPricingPipeline),
        ];

        $this->customPriceDebugLogger->log(
            $payload['event'] . ' ' . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR)
        );
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function extractCustomPriceRelatedPostKeys(array $post): array
    {
        $keys = [
            'custom_price',
            'qty',
            'product',
            'selected_option_child_id',
            'selected_configurable_option',
            'RoomWidth',
            'RoomLength',
            'CustomerRoomWidth',
            'CustomerRoomLength',
            'CustomerRoomLengthGroup',
            'RollType',
            'custom_length',
            'pricePerSqft',
            'PricePerLinearFoot',
            'RollSize',
            'ship_to_text',
            'overage_percent',
            'product_floor_type',
            'main_product_id',
            'pids',
            'colors',
        ];
        $out = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $post)) {
                continue;
            }
            $out[$key] = $post[$key];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractRequestPriceParamsForLog(): array
    {
        $params = [
            'custom_price',
            'product_floor_type',
            'price_per_tile',
            'trailer_rolls_price',
            'main_product_id',
        ];
        $out = [];
        foreach ($params as $param) {
            $v = $this->request->getParam($param);
            if ($v !== null && $v !== '') {
                $out[$param] = $v;
            }
        }

        return $out;
    }

    /**
     * Request post data may be Laminas Parameters; normalize so we can pass by reference as array.
     */
    private function ensurePostArray(mixed $post): array
    {
        if ($post instanceof Parameters) {
            return $post->toArray();
        }

        return is_array($post) ? $post : (array) $post;
    }

    /**
     * Room area × Advance Search $/sqft (same formula as reorder). Returns whether a positive line unit price was applied.
     */
    private function applyRoomAreaCustomPriceIfApplicable(
        AbstractItem $item,
        array &$post,
        Product $childProduct
    ): bool {
        if (!isset($post['RoomWidth'], $post['RoomLength']) || $childProduct->getId() === null) {
            return false;
        }
        if ((float) $post['RoomWidth'] <= 0 || (float) $post['RoomLength'] <= 0) {
            return false;
        }
        $getCalculatedPrice = $this->advanceSearchData->getCalculatedPrice((int) $childProduct->getId());
        if (!isset($getCalculatedPrice['price']) || (float) $getCalculatedPrice['price'] <= 0) {
            return false;
        }
        $customPrice = (float) $post['RoomWidth'] * (float) $post['RoomLength'] * (float) $getCalculatedPrice['price'];
        if ($customPrice <= 0) {
            return false;
        }
        $targetItem = $item->getParentItem() ?: $item;
        $post['custom_price'] = $customPrice;
        $targetItem->setCustomPrice($customPrice);
        $targetItem->setOriginalCustomPrice($customPrice);
        $targetItem->getProduct()->setIsSuperMode(true);

        return true;
    }

    /**
     * PDP flooring custom prices (merged from Dcw\FlooringCalculation\Observer\CustomPrice).
     * Invalid JS values (NaN, etc.) fall back via CustomPriceSanitizer.
     * Request values that sanitize to 0 are ignored so they do not overwrite room-area pricing.
     */
    private function applyFlooringCustomPriceFromRequest(AbstractItem $item, array &$post): void
    {
        $productFloorType = (string) $this->request->getParam('product_floor_type');
        $targetItem = $item->getParentItem() ?: $item;
        $product = $targetItem->getProduct();

        if ($productFloorType === 'CBC Tiles') {
            $raw = $this->request->getParam('price_per_tile');
            $rounded = round((float) $raw, 2);
            $sanitized = $this->customPriceSanitizer->resolveUnitPrice($rounded, $product);
            if ($sanitized <= 0.0) {
                return;
            }
            $targetItem->setCustomPrice($sanitized);
            $targetItem->setOriginalCustomPrice($sanitized);
            $product->setIsSuperMode(true);
            $post['custom_price'] = $this->customPriceSanitizer->formatUnitPriceForStorage($sanitized);

            return;
        }

        if ($productFloorType === 'Trailer Rolls') {
            $raw = $this->request->getParam('trailer_rolls_price');
            $rounded = round((float) $raw, 2);
            $sanitized = $this->customPriceSanitizer->resolveUnitPrice($rounded, $product);
            if ($sanitized <= 0.0) {
                return;
            }
            $targetItem->setCustomPrice($sanitized);
            $targetItem->setOriginalCustomPrice($sanitized);
            $product->setIsSuperMode(true);
            $post['custom_price'] = $this->customPriceSanitizer->formatUnitPriceForStorage($sanitized);

            return;
        }

        $customPriceParam = $this->request->getParam('custom_price');
        if ($customPriceParam === null || $customPriceParam === '') {
            return;
        }

        $sanitized = $this->customPriceSanitizer->resolveUnitPrice($customPriceParam, $product);
        if ($sanitized <= 0.0) {
            return;
        }
        $targetItem->setCustomPrice($sanitized);
        $targetItem->setOriginalCustomPrice($sanitized);
        $product->setIsSuperMode(true);
        $post['custom_price'] = $this->customPriceSanitizer->formatUnitPriceForStorage($sanitized);
    }
}
