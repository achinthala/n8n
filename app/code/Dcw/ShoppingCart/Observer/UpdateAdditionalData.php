<?php
declare(strict_types=1);

namespace Dcw\ShoppingCart\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Dcw\ShoppingCart\ViewModel\Data as ShoppingCartData;
use Dcw\FlooringCalculation\ViewModel\Data as FlooringCalculationViewModel;
use Magento\Framework\Exception\LocalizedException;

class UpdateAdditionalData implements ObserverInterface
{
	/**
	 * @var RequestInterface
	 */
	protected $_request;
	/**
	 * @var SerializerInterface
	 */
	private $serializer;
	/**
     * @var ManagerInterface
     */
    protected $messageManager;
	/**
	 * @var ProductRepositoryInterface
	 */
	protected $productRepository;
	/**
	 * @var ShoppingCartData
	 */
	protected $shoppingCartData;
	/**
	 * @var FlooringCalculationViewModel
	 */
	protected $flooringCalculationViewModel;

	public function __construct(
		RequestInterface $request,
		SerializerInterface $serializer,
		ManagerInterface $messageManager,
		ProductRepositoryInterface $productRepository,
		ShoppingCartData $shoppingCartData,
		FlooringCalculationViewModel $flooringCalculationViewModel
	) {
		$this->_request = $request;
		$this->serializer = $serializer;
		$this->messageManager = $messageManager;
		$this->productRepository = $productRepository;
		$this->shoppingCartData = $shoppingCartData;
		$this->flooringCalculationViewModel = $flooringCalculationViewModel;
	}
	
	/**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quoteItem = $observer->getEvent()->getQuoteItem();

		$product = $this->productRepository->get($quoteItem->getSku());
        
		$calculatorType = $product->getAttributeText('incstores_pim_calculator_type');

		if ($additionalOption = $quoteItem->getOptionByCode('additional_options')) {
			$additionalOptions = $this->serializer->unserialize($additionalOption->getValue());
		}

		$params = $this->_request->getParams();
		$qty = $params['qty'] ?? 1;
		$isRollProduct = false;
		
		if (isset($params['RoomWidth']) && isset($params['RoomLength']) && $params['RoomWidth'] > 0 && $params['RoomLength'] > 0 && $params['RollType'] == 'Recommended Roll Length') {
			$additionalOptions['room_width'] = [
				'label' => 'Roll Width',
				'value' => $params['RoomWidth']
			];  
			$additionalOptions['room_length'] = [
				'label' => 'Roll Length',
				'value' => $params['RoomLength']
			];
			$isRollProduct = true;
		} else if (isset($params['custom_length']) && $params['custom_length'] > 0 && $params['RollType'] == 'Custom Roll Length') {
			$additionalOptions['custom_length'] = [
				'label' => 'Custom Length',
				'value' => $params['custom_length']
			];
			$isRollProduct = true;
		}

		if ($isRollProduct) {
			$getMiniCartQty = $this->flooringCalculationViewModel->getMiniCartQty($product->getSku());
			$requestedQty = (float)$params['RoomLength'] * (float)$qty;
			$totalRequestedQty = $getMiniCartQty + $requestedQty;
			$isProductCanAddToCart = $this->flooringCalculationViewModel->isProductCanAddToCart($product->getId(), $totalRequestedQty);
			$availableQty = $this->flooringCalculationViewModel->getProductAvailableQty($product->getId());

			if (!$isProductCanAddToCart) {
				throw new LocalizedException(__('You can\'t order more than %1 linear feet. Requested: %2 linear feet.', $availableQty, $totalRequestedQty));
			}
		}
		
		if (!empty($additionalOptions) && $calculatorType =='roll') {
			$quoteItem->addOption([
				'product_id' => $quoteItem->getProductId(),
				'code' => 'additional_options',
				'value' => $this->serializer->serialize($additionalOptions)
			]);
		}

		$pdplinedata = [];

		if (isset($params['CustomerRoomWidth'])) {
			$pdplinedata['CustomerRoomWidth']= $params['CustomerRoomWidth'] ?? 0;
		}

		if(isset($params['CustomerRoomLength'])){
			$pdplinedata['CustomerRoomLength']= $params['CustomerRoomLength'] ?? 0;
		}

		if(isset($params['CustomerRoomLengthGroup'])){
			$pdplinedata['CustomerRoomLengthGroup']= $params['CustomerRoomLengthGroup'] ?? 0;
		}
		
		if (isset($params['custom_length'])) {
			$pdplinedata['CustomLength']= $params['custom_length'] ?? 0;
		}

		if (isset($params['pricePerSqft'])) {
			$pdplinedata['PricePerSqft']= number_format((float)$params['pricePerSqft'], 2, '.', '') ?? 0;
		}

		$pdplinedata['LineItemUnitQuantity'] = $qty;

		if (isset($post['overage_percent'])) {
			$pdplinedata['OveragePercent']= $post['overage_percent'] ?? 0;
		}

		if (isset($params['custom_price'])) {
			$pdplinedata['UnitPrice']= $params['custom_price'] ?? 0;
		}

		if(isset($params['custom_price'])){
			$pdplinedata['LineItemPrice']= ((float)$params['custom_price'] * (int)$qty) ?? 0;
		}

		if(isset($params['tileSize'])){
			$pdplinedata['TileSize']= $params['tileSize'] ?? 0;
		}
		
		if(isset($params['covers'])){
			$pdplinedata['Covers']= $params['covers'] ?? 0;
		}
		
		if(isset($params['squareFootage'])){
			$pdplinedata['SquareFootage']= $params['squareFootage'] ?? 0;
		}
		
		if(isset($params['length'])){
			$pdplinedata['Length']= $params['length'] ?? 0;
		}

		if(isset($params['Width'])){
			$pdplinedata['Width']= $params['width'] ?? 0;
		}

		if(isset($params['PricePerLinearFoot'])){
			$pdplinedata['PricePerLinearFoot']= $params['PricePerLinearFoot'] ?? 0;
		}

		if(isset($params['RoomWidth'])){
			$pdplinedata['RoomWidth']= $params['RoomWidth'] ?? 0;
		}

		if(isset($params['RoomLength'])){
			$pdplinedata['RoomLength']= $params['RoomLength'] ?? 0;
		}
		
		if(isset($params['RollType'])){
			$pdplinedata['RollType']= $params['RollType'] ?? 0;
		}
		
		if(isset($params['QuantityRange'])){
			$pdplinedata['QuantityRange']= $params['QuantityRange'] ?? 0;
		}
		
		if(isset($params['RollSize'])){
			$pdplinedata['RollSize']= $params['RollSize'] ?? '';
		}
		
		if(isset($params['TrailerRollType'])){
			$pdplinedata['TrailerRollType']= $params['TrailerRollType'] ?? 0;
		}
		
		if(isset($params['CBCShopBy'])){
			$pdplinedata['CBCShopBy']= $params['CBCShopBy'] ?? 0;
		}
		
		if(isset($params['CBCTileSize'])){
			$pdplinedata['CBCTileSize']= $params['CBCTileSize'] ?? 0;
		}

		if(isset($params['CBCShopBy'])){
			$pdplinedata['CBCShopBy']= $params['CBCShopBy'] ?? 0;
		}

		if(isset($params['border_tiles_qty'])){
			$pdplinedata['BorderTileQty']= $params['border_tiles_qty'] ?? 0;
		}

		if(isset($params['center_tiles_qty'])){
			$pdplinedata['CenterTileQty']= $params['center_tiles_qty'] ?? 0;
		}

		if(isset($params['corner_tiles_qty'])){
			$pdplinedata['CornerTileQty']= $params['corner_tiles_qty'] ?? 0;
		}

		$checkForQtyBackorder = $this->shoppingCartData->checkForQtyBackorder($product, $qty);

		if(isset($params['ship_to_text']) && !$checkForQtyBackorder){
			$pdplinedata['shipping_estimate'] = $params['ship_to_text'] ?? '';
		} else {
			$pdplinedata['shipping_estimate'] = "";
		}

		$checkForPromiseDate = $this->shoppingCartData->getProductPromiseDateFromProductObject($product, $quoteItem->getQty());

		if ($checkForPromiseDate) {
			$pdplinedata['promise_date']= $checkForPromiseDate;
		}

		if(!empty($pdplinedata) && $calculatorType =='roll'){
			$pdplinedata_json = json_encode($pdplinedata);
			$quoteItem->setPdpLineItem($pdplinedata_json);

			// If it's a parent item (configurable product), get the child (simple product)
			if ($quoteItem->getProductType() == 'configurable') {
				$children = $quoteItem->getChildren();
	
				// Loop through children (simple products)
				foreach ($children as $childItem) {
					$childItem->setData('pdp_line_item', $pdplinedata_json);
				}
			}
		}

		if (isset($params['custom_price']) && $params['custom_price']!="" && $calculatorType =='roll') {
			$item = ( $quoteItem->getParentItem() ? $quoteItem->getParentItem() : $quoteItem );
			$price = $params['custom_price']; //set your price here
			$item->setCustomPrice($price);
			$item->setOriginalCustomPrice($price);
			$item->getProduct()->setIsSuperMode(true);
		}

		$this->messageManager->addSuccessMessage(__('Product updated successfully.'));

		return $this;
    }
}
