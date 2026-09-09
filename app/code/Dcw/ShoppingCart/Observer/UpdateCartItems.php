<?php
declare(strict_types=1);

namespace Dcw\ShoppingCart\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Catalog\Model\ProductFactory;
use Dcw\FlooringCalculation\Helper\Data;
use Dcw\ShoppingCart\ViewModel\Data as ShoppingCartData;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Dcw\FlooringCalculation\Helper\Data as FlooringCalculationHelper;
use Dcw\FlooringCalculation\ViewModel\Data as FlooringCalculationViewModel;
use Magento\Framework\Exception\LocalizedException;

class UpdateCartItems implements ObserverInterface
{
	private $serializer;
	protected $_productloader;
	protected $dataHelper;
	protected $shoppingCartData;
	protected $productRepository;
	protected $flooringCalculationHelper;
	protected $flooringCalculationViewModel;

    public function __construct(
        SerializerInterface $serializer,
		ProductFactory $_productloader,
		Data $dataHelper,
		ShoppingCartData $shoppingCartData,
		ProductRepositoryInterface $productRepository,
		FlooringCalculationHelper $flooringCalculationHelper,
		FlooringCalculationViewModel $flooringCalculationViewModel
    )
    {
		$this->serializer = $serializer;
		$this->_productloader = $_productloader;
		$this->dataHelper = $dataHelper;
		$this->shoppingCartData = $shoppingCartData;
		$this->productRepository = $productRepository;
		$this->flooringCalculationHelper = $flooringCalculationHelper;
		$this->flooringCalculationViewModel = $flooringCalculationViewModel;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
		// Get the cart and quote items
        $quote = $observer->getCart()->getQuote();
        $items = $quote->getAllItems(); // Get all quote items

        $this->stockCheckWhileProductUpdate($items);

        foreach ($items as $item) {
			$qtyweight = "";

			$getPdpLineItem = $item->getPdpLineItem();

			if ($getPdpLineItem) {

				try {
					$childProduct = $this->productRepository->get($item->getSku());
					$checkForPromiseDate = $this->shoppingCartData->getProductPromiseDateFromProductObject($childProduct, $item->getQty());
				} catch (NoSuchEntityException $e) {
					//do nothing
				}

				if ($item->getProductType() == 'simple') {
					$parentItem = $item->getParentItem(); // Check if the item has a parent

					if ($parentItem) {
						$item->setQty($parentItem->getQty());
					}
				}

				// Decode the JSON string into an associative array
				$data = json_decode($getPdpLineItem, true);
				$rollWidth = "";
				if(isset($data['RoomWidth'])){
					$rollWidth = $data['RoomWidth'];
				}
				$rollLength = "";
				if(isset($data['RoomLength'])){
					$rollLength = $data['RoomLength'];
				}

				if (isset($rollWidth) && isset($rollLength) && !empty($rollLength)) {
					$data['RollSize'] = $item->getQty()." roll(s) ".$rollWidth."′ × ".$rollLength." ′ ea.";
				}

				// Update the LineItemUnitQuantity value
				$data['LineItemUnitQuantity'] = (float)$item->getQty();

				$data['UnitPrice'] = (float)($data['UnitPrice'] ?? 0.00);

				if (!$data['UnitPrice']) {
					$item->setQty(1);
				}

				$data['LineItemPrice'] = ((float)$data['UnitPrice'] * $data['LineItemUnitQuantity']) ?? 0.0;

				if ($checkForPromiseDate) {
					$data['promise_date']= $checkForPromiseDate;
				}

				$checkForQtyBackorder = $this->shoppingCartData->checkForQtyBackorder($childProduct, $item->getQty());

				if(!$checkForQtyBackorder){
					$shipToText = $this->flooringCalculationHelper->getExpectedShipDate($childProduct);
					$data['shipping_estimate'] = $shipToText;
				} else {
					$data['shipping_estimate'] = "";
				}

				// Encode the array back to a JSON string
				$updatedJsonString = json_encode($data);

				$item->setPdpLineItem($updatedJsonString);
				$item->save();
			}

			$additionalOptions = [];

			if ($item->getProduct()) {
				if ($additionalOption = $item->getOptionByCode('additional_options')) {
					$additionalOptions = $this->serializer->unserialize($additionalOption->getValue());
				}

				if (!empty($additionalOptions)) {
					foreach ($additionalOptions as $key=>$option) {
						if ($key == "ship_to_text") {
							$additionalOptions['ship_to_text'] = [
								'label' => '',
								'value' => $option['value']
							];
						} elseif ($key == "weight" && $item->getWeight()!="") {
							$qtyweight = intval($item->getWeight())*$item->getQty();
							$additionalOptions['weight'] = [
								'label' => 'Weight',
								'value' => $qtyweight.' lbs'
							];

						} elseif ($key == "customrool_length") {
							$additionalOptions['customrool_length'] = [
								'label' => 'Custom Roll Length',
								'value' => $option['value']
							];
						}
					}
					$item->addOption([
						'code' => 'additional_options',
						'value' => $this->serializer->serialize($additionalOptions),
						'product_id' => $item->getProduct()->getId()
					]);
				}
			}
		}

		$quote->save();
	}

    /**
     * Check the stock while product update
     * 
     * @param \Magento\Quote\Api\Data\CartItemInterface[] $allItems
     * @return void
     */
    public function stockCheckWhileProductUpdate($allItems)
    {
        $quoteItemQtyArray = [];
        
        foreach ($allItems as $item) {
            if ($item->getProductType() == 'configurable') {
                $childProduct = $this->productRepository->get($item->getSku());
                $getPdpLineItem = $item->getPdpLineItem();

                if ($getPdpLineItem) {
                    try {
                        $data = json_decode($getPdpLineItem, true);
                        $rollWidth = 0;
                        if (isset($data['RoomWidth'])) {
                            $rollWidth = $data['RoomWidth'];
                        }
                        $rollLength = 0;
                        if (isset($data['RoomLength'])) {
                            $rollLength = $data['RoomLength'];
                        }

                        if (isset($rollWidth) && isset($rollLength) && !empty($rollLength)) {
                            $calculatedQty = (float) $rollLength * (float) $item->getQty();
                            
                            // Append qty if same SKU is found
                            if (isset($quoteItemQtyArray[$item->getSku()])) {
                                $quoteItemQtyArray[$item->getSku()]['qty'] += $calculatedQty;
                                $quoteItemQtyArray[$item->getSku()]['id'] = $childProduct->getId();
                            } else {
                                $quoteItemQtyArray[$item->getSku()] = [
                                    'id' => (int) $childProduct->getId(),
                                    'qty' => (float) $calculatedQty
                                ];
                            }
                        }
                    } catch (NoSuchEntityException $e) {
                        //do nothing
                    }
                }
            }
        }

        foreach ($quoteItemQtyArray as $item) {
            $availableQty = $this->flooringCalculationViewModel->getProductAvailableQty($item['id']);

            if ($availableQty < $item['qty']) {
                throw new LocalizedException(__('You can\'t order more than %1 linear feet. Requested: %2 linear feet.', $availableQty, $item['qty']));
            }
        }
    }
}
