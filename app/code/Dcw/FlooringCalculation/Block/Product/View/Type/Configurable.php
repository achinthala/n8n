<?php
declare(strict_types=1);

namespace Dcw\FlooringCalculation\Block\Product\View\Type;

use Dcw\FlooringCalculation\Helper\Data as FlooringCalculationHelper;
use Dcw\FlooringCalculation\ViewModel\Data;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ProductConfigurable;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Dcw\AdvanceSearch\ViewModel\Data as AdvanceSearchViewModel;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class Configurable
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly FlooringCalculationHelper $flooringCalcHelper,
        private readonly Data $viewModelData,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger,
		private readonly StockRegistryInterface $stockRegistry,
		private readonly AdvanceSearchViewModel $advanceSearchViewModel,
		private readonly ProductCollectionFactory $productCollectionFactory,
    ) {
    }

    /**
     * Plugin to ensure all required attributes are loaded in the collection
     * This prevents individual product loads later
     */
    public function afterGetAllowProducts(ProductConfigurable $subject, $result)
    {
        if (is_array($result) || $result instanceof \Traversable) {
            $requiredAttributes = [
                'incstores_pim_ships_from_region',
                'incstores_pim_min_rollcut',
                'incstores_pim_calculator_type',
                'incstores_pim_unit_of_measure',
                'incstores_pim_alt_unit_of_measure',
                'incstores_pim_has_corner_border_center',
                'incstores_pim_max_roll_legnth',
                'incstores_pim_exact_width_inches',
                'incstores_pim_exact_length_inches',
                'incstores_pim_exact_height_inches',
                'incstores_pim_coverage',
                'incstores_pim_shipping_program',
                'incstores_pim_percent_overage',
                'incstores_pim_visible_when_oos',
                'roll_cut_type'
            ];
            
            // If result is a collection, add attributes to it
            if ($result instanceof Collection) {
                foreach ($requiredAttributes as $attribute) {
                    $result->addAttributeToSelect($attribute);
                }
            } else {
                // If it's an array, batch load all products that need attributes
                // This prevents individual product loads which are extremely slow
                $productIdsToLoad = [];
                $productsById = [];
                
                foreach ($result as $product) {
                    if ($product instanceof \Magento\Catalog\Model\Product) {
                        $productId = $product->getId();
                        $productsById[$productId] = $product;
                        
                        // Check if any required attribute is missing
                        $needsLoad = false;
                        foreach ($requiredAttributes as $attribute) {
                            if (!$product->hasData($attribute)) {
                                $needsLoad = true;
                                break;
                            }
                        }
                        
                        if ($needsLoad && !in_array($productId, $productIdsToLoad)) {
                            $productIdsToLoad[] = $productId;
                        }
                    }
                }
                
                // Batch load all products that need attributes in a single query
                if (!empty($productIdsToLoad)) {
                    $collection = $this->productCollectionFactory->create();
                    $collection->addAttributeToSelect($requiredAttributes);
                    $collection->addFieldToFilter('entity_id', ['in' => $productIdsToLoad]);
                    
                    // Load the collection and merge data into existing product objects
                    foreach ($collection as $loadedProduct) {
                        $productId = $loadedProduct->getId();
                        if (isset($productsById[$productId])) {
                            // Merge loaded attribute data into existing product object
                            foreach ($requiredAttributes as $attribute) {
                                $value = $loadedProduct->getData($attribute);
                                if ($value !== null) {
                                    $productsById[$productId]->setData($attribute, $value);
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $result;
    }

    public function afterGetJsonConfig(ProductConfigurable $subject, string $result): string
    {
        try {
            $resultUnserialized = $this->serializer->unserialize($result);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error($exception->getTraceAsString(), [$result]);

            return $result;
        }

        $finalData = $this->allProductsAttributesData($subject);
		
		//  ADD CUSTOM PRICE TO optionPrices
		if (isset($resultUnserialized['optionPrices'])) {
			foreach ($resultUnserialized['optionPrices'] as $productId => &$priceData) {

				// Example: fetch your custom price
				$customPrice = $finalData['incstores_pim_custom_price'][$productId] ?? null;

				if ($customPrice !== null) {
					$priceData['customPrice'] = [
						'amount' => (float)$customPrice
					];
				}
			}
		}

        $resultUnserialized['min_rollcut'] = $finalData['min_rollcut'] ?? [];
        $resultUnserialized['calculator_type'] = $finalData['calculator_type'] ?? [];
        $resultUnserialized['has_corner_border_center'] = $finalData['has_corner_border_center'] ?? [];
        $resultUnserialized['max_roll_length_restriction'] = $finalData['max_roll_length_restriction'] ?? [];
        $resultUnserialized['exact_width'] = $finalData['exact_width'] ?? [];
        $resultUnserialized['exact_length'] = $finalData['exact_length'] ?? [];
        $resultUnserialized['exact_height'] = $finalData['exact_height'] ?? [];
        $resultUnserialized['coverage_per_case'] = $finalData['coverage_per_case'] ?? [];
        $resultUnserialized['min_tile_quantity'] = $finalData['min_tile_quantity'] ?? [];
        $resultUnserialized['pdp_shipping_text'] = $finalData['pdp_shipping_text'] ?? [];
        $resultUnserialized['expected_days_ship'] = $finalData['expected_days_ship'] ?? [];
        $resultUnserialized['quick_ship'] = $finalData['quick_ship'] ?? [];
        $resultUnserialized['overage_percent'] = $finalData['overage_percent'] ?? [];
        $resultUnserialized['unit_of_measure'] = $finalData['unit_of_measure'] ?? [];
        $resultUnserialized['alt_unit_of_measure'] = $finalData['alt_unit_of_measure'] ?? [];
        $resultUnserialized['available_quantity'] = $finalData['available_quantity'] ?? [];
        
        try {
            return $this->serializer->serialize($resultUnserialized);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error($exception->getTraceAsString(), [$result]);

            return $result;
        }
    }

    private function allProductsAttributesData($subject)
    {
        $getAllData = [];
        $products = $subject->getAllowProducts();
        
        // Collect all product IDs for batch operations
        $allProductIds = [];
        foreach ($products as $product) {
            $allProductIds[] = $product->getId();
        }
        
        // Batch load stock items and available quantities for all products in a single query
        $stockData = $this->viewModelData->getProductsStockItemsBatch($allProductIds);
        $stockItems = $stockData['stock_items'];
        $availableQuantities = $stockData['available_quantities'];

        foreach ($products as $product) {
            $productId = $product->getId();

            //available quantity - use batch loaded data
            $availableQuantity = $availableQuantities[$productId] ?? 0;
            $getAllData['available_quantity'][$productId] = $availableQuantity;

            //Min roll cut
            $minRollCut = '';

            if (!empty($product->getIncstoresPimMinRollcut())) {
                $minRollCut = $product->getIncstoresPimMinRollcut();
            }

            $getAllData['min_rollcut'][$productId] = $minRollCut;
			
			// Use product object instead of productId to avoid reloading
			$incstores_pim_custom_price = $this->advanceSearchViewModel->getCalculatedPriceForProduct($product, 'final');
			$getAllData['incstores_pim_custom_price'][$productId] = $incstores_pim_custom_price['price'] ?? 0;

            //Calculator Type
            $calculatorType = 'none';
            $getIncstoresPimCalculatorType = $product->getAttributeText('incstores_pim_calculator_type');

            if (!empty($product->getIncstoresPimCalculatorType()) && $getIncstoresPimCalculatorType != 'none') {
                $calculatorType = $getIncstoresPimCalculatorType;
            }

            $getAllData['calculator_type'][$productId] = $calculatorType;

            $getIncstoresPimUnitOfMeasure = $product->getAttributeText('incstores_pim_unit_of_measure');

            if (!empty($getIncstoresPimUnitOfMeasure)) {
                $getAllData['unit_of_measure'][$productId] = strtolower($getIncstoresPimUnitOfMeasure);
            }

            $getIncstoresPimAltUnitOfMeasure = $product->getAttributeText('incstores_pim_alt_unit_of_measure');
            if (!empty($getIncstoresPimAltUnitOfMeasure)) {
                $getAllData['alt_unit_of_measure'][$productId] = strtolower($getIncstoresPimAltUnitOfMeasure);
            }

            //Has corner border center
            $has_corner_border_center = 0;

            if (!empty($product->getIncstoresPimHasCornerBorderCenter())) {
                $has_corner_border_center = $product->getIncstoresPimHasCornerBorderCenter();
            }

            $getAllData['has_corner_border_center'][$productId] = $has_corner_border_center;

            //Max roll length restriction
            $max_roll_cut = '';

            if (!empty($product->getIncstoresPimMaxRollLegnth())) {
                $max_roll_cut = $product->getIncstoresPimMaxRollLegnth();
            }

            $getAllData['max_roll_length_restriction'][$productId] = $max_roll_cut;

            //Exact width
            $exact_width = '';

            if (!empty($product->getIncstoresPimExactWidthInches())) {
                $exact_width = $product->getIncstoresPimExactWidthInches();
            }

            $getAllData['exact_width'][$productId] = $exact_width;

            //Exact length
            $exact_length = '';

            if (!empty($product->getIncstoresPimExactLengthInches())) {
                $exact_length = $product->getIncstoresPimExactLengthInches();
            }

            $getAllData['exact_length'][$productId] = $exact_length;

            //Exact height
            $exact_height = '';

            if (!empty($product->getIncstoresPimExactHeightInches())) {
                $exact_height = $product->getIncstoresPimExactHeightInches();
            }

            $getAllData['exact_height'][$productId] = $exact_height;

            //Coverage per case
            $coverage_per_case = '';

            if (!empty($product->getIncstoresPimCoverage())) {
                $coverage_per_case = $product->getIncstoresPimCoverage();
            }

            $getAllData['coverage_per_case'][$productId] = $coverage_per_case;

            //Min tile quantity
            $tile_min_qty = 1;

            if (!empty($product->getIncstoresPimMinRollcut())) {
                $tile_min_qty = $product->getIncstoresPimMinRollcut();
            }

            $getAllData['min_tile_quantity'][$productId] = $tile_min_qty;

            //Shipping program - use batch loaded stock data
            $pdp_shipping_text = '';
            $getIncstoresPimShippingProgram = $product->getAttributeText('incstores_pim_shipping_program');
			
			// Get stock item data from batch loaded data
            $stockItemData = $stockItems[$productId] ?? ['qty' => 0, 'is_in_stock' => false];
            $availableQty = $stockItemData['qty'];
			
			// Get Visible When OOS flag (cast to integer)
            $isVisibleWhenOOS = (int) $product->getIncstoresPimVisibleWhenOos();
			
			if ($availableQty <= 0 && $isVisibleWhenOOS == 1){
				$pdp_shipping_text = 'Pre-Order Now';
			} elseif (!empty($product->getIncstoresPimShippingProgram()) && $getIncstoresPimShippingProgram != 'none') {
                $pdp_shipping_text = $getIncstoresPimShippingProgram;
            }

            $getAllData['pdp_shipping_text'][$productId] = $pdp_shipping_text;


            //Expected ship date
			if ($availableQty <= 0 && $isVisibleWhenOOS == 1){
				$expected_days_ship = $this->flooringCalcHelper->getExpectedShipDateOOSYes($product);
			}else{
				$expected_days_ship = $this->flooringCalcHelper->getExpectedShipDate($product);
			}
            $getAllData['expected_days_ship'][$productId] = $expected_days_ship;

            //Overage Percent
            $overage_percent = '';

            if (!empty($product->getIncstoresPimPercentOverage())) {
                $overage_percent = $product->getIncstoresPimPercentOverage();
            }

            $getAllData['overage_percent'][$productId] = $overage_percent;
        }
        
        // Quick Ship calculation - batch process all products with store switching done once
        $adminStoreId = Store::ADMIN_CODE;
        $currentStoreId = $this->storeManager->getStore()->getId();
        $this->storeManager->setCurrentStore($adminStoreId);
        
        foreach ($products as $product) {
            $productId = $product->getId();
            $shippingProgramText = (string)$product->getAttributeText('incstores_pim_shipping_program');
            $shippingProgramparts = explode('|', $shippingProgramText);
            $shippingProgram = $pdp_quick_ship = '';

            if (count($shippingProgramparts) >= 2) {
                $shippingProgram = $shippingProgramparts[0];
            }

            if (!empty($product->getIncstoresPimShippingProgram()) && $shippingProgram != 'none' &&
                $shippingProgram != 'free_ship') {
                $pdp_quick_ship = 1;
            }

            $getAllData['quick_ship'][$productId] = $pdp_quick_ship;
        }
        
        // Restore original store
        $this->storeManager->setCurrentStore($currentStoreId);

        return $getAllData;
    }

    public function getCalculatorType($subject)
    {
        $calculatorTypeArray = [];

        foreach ($subject->getAllowProducts() as $product) {
            $calculatorType = 'none';
            $getIncstoresPimCalculatorType = $product->getAttributeText('incstores_pim_calculator_type');

            if (!empty($product->getIncstoresPimCalculatorType()) && $getIncstoresPimCalculatorType != 'none') {
                $calculatorType = $getIncstoresPimCalculatorType;
            }

            $calculatorTypeArray[$product->getId()] = $calculatorType;
        }

        return $calculatorTypeArray;
    }

    public function getHaCornerBorderCenter($subject)
    {
        $hasCornerBorderCenter = [];

        foreach ($subject->getAllowProducts() as $product) {
            $has_corner_border_center = 0;

            if (!empty($product->getIncstoresPimHasCornerBorderCenter())) {
                $has_corner_border_center = $product->getIncstoresPimHasCornerBorderCenter();
            }

            $hasCornerBorderCenter[$product->getId()] = $has_corner_border_center;
        }

        return $hasCornerBorderCenter;
    }

    public function getRollCutType($subject)
    {
        $rollCutType = [];

        foreach ($subject->getAllowProducts() as $product) {
            $roll_cut_type = '';

            if (!empty($product->getRollCutType())) {
                $roll_cut_type = $product->getRollCutType();
            }

            $rollCutType[$product->getId()] = $roll_cut_type;
        }

        return $rollCutType;
    }

    public function getMaxRollLengthRestriction($subject)
    {
        $maxRollLengthRestriction = [];

        foreach ($subject->getAllowProducts() as $product) {
            $max_roll_cut = '';

            if (!empty($product->getIncstoresPimMaxRollLegnth())) {
                $max_roll_cut = $product->getIncstoresPimMaxRollLegnth();
            }

            $maxRollLengthRestriction[$product->getId()] = $max_roll_cut;
        }

        return $maxRollLengthRestriction;
    }

    public function getMinRollcut($subject)
    {
        $minRollcut = [];

        foreach ($subject->getAllowProducts() as $product) {
            $minRollCut = '';

            if (!empty($product->getIncstoresPimMinRollcut())) {
                $minRollCut = $product->getIncstoresPimMinRollcut();
            }

            $minRollcut[$product->getId()] = $minRollCut;
        }

        return $minRollcut;
    }

    public function getTileSizeArea($subject)
    {
        $tileSizeAreaArr = [];

        foreach ($subject->getAllowProducts() as $product) {
            $tile_size_area = '';
            /* if (!empty($product->getTileSizeArea())) {
                 $tile_size_area = $product->getTileSizeArea();
             } else {
                 $tile_size_area = '';
             } */
            $tileSizeAreaArr[$product->getId()] = $tile_size_area;
        }
        return $tileSizeAreaArr;
    }

    public function getIncstoresPimExactWidthInches($subject)
    {
        $exactWidthArr = [];

        foreach ($subject->getAllowProducts() as $product) {
            $exact_width = '';

            if (!empty($product->getIncstoresPimExactWidthInches())) {
                $exact_width = $product->getIncstoresPimExactWidthInches();
            }

            $exactWidthArr[$product->getId()] = $exact_width;
        }

        return $exactWidthArr;
    }

    public function getIncstoresPimExactLengthInches($subject)
    {
        $exactLengthArr = [];

        foreach ($subject->getAllowProducts() as $product) {
            $exact_length = '';

            if (!empty($product->getIncstoresPimExactLengthInches())) {
                $exact_length = $product->getIncstoresPimExactLengthInches();
            }

            $exactLengthArr[$product->getId()] = $exact_length;
        }

        return $exactLengthArr;
    }

    public function getIncstoresPimExactHeightInches($subject)
    {
        $exactHeightArr = [];

        foreach ($subject->getAllowProducts() as $product) {
            $exact_height = '';

            if (!empty($product->getIncstoresPimExactHeightInches())) {
                $exact_height = $product->getIncstoresPimExactHeightInches();
            }

            $exactHeightArr[$product->getId()] = $exact_height;
        }

        return $exactHeightArr;
    }

    public function getIncstoresPimCoverage($subject)
    {
        $coveragePerCaseArr = [];

        foreach ($subject->getAllowProducts() as $product) {
            $coverage_per_case = '';

            if (!empty($product->getIncstoresPimCoverage())) {
                $coverage_per_case = $product->getIncstoresPimCoverage();
            }

            $coveragePerCaseArr[$product->getId()] = $coverage_per_case;
        }

        return $coveragePerCaseArr;
    }

    public function getIncstoresPimMinOrderQty($subject)
    {
        $tileMinQty = [];

        foreach ($subject->getAllowProducts() as $product) {
            $tile_min_qty = 1;

            if (!empty($product->getIncstoresPimMinRollcut())) {
                $tile_min_qty = $product->getIncstoresPimMinRollcut();
            }

            $tileMinQty[$product->getId()] = $tile_min_qty;
        }

        return $tileMinQty;
    }

    public function getIncstoresPimShippingDescription($subject)
    {
        $pdpShippingTextArr = [];

        foreach ($subject->getAllowProducts() as $product) {
            $pdp_shipping_text = '';
            $getIncstoresPimShippingProgram = $product->getAttributeText('incstores_pim_shipping_program');

            if (!empty($product->getIncstoresPimShippingProgram()) && $getIncstoresPimShippingProgram != 'none') {
                $pdp_shipping_text = $getIncstoresPimShippingProgram;
            }

            $pdpShippingTextArr[$product->getId()] = $pdp_shipping_text;
        }

        return $pdpShippingTextArr;
    }

    public function getExpectedDaysShip($subject)
    {
        $pdpShippingTextArr = [];

        foreach ($subject->getAllowProducts() as $product) {
            $expected_days_ship = $this->flooringCalcHelper->getExpectedShipDate($product);
            $pdpShippingTextArr[$product->getId()] = $expected_days_ship;
        }

        return $pdpShippingTextArr;
    }

    public function getQuickShip($subject)
    {
        $adminStoreId = Store::ADMIN_CODE;
        $this->storeManager->setCurrentStore($adminStoreId);

        $pdpQuickShipArr = [];

        foreach ($subject->getAllowProducts() as $product) {
            $shippingProgramText = (string)$product->getAttributeText('incstores_pim_shipping_program');

            $shippingProgramparts = explode('|', $shippingProgramText);
            $shippingProgram = $pdp_quick_ship = '';

            if (count($shippingProgramparts) >= 2) {
                $shippingProgram = $shippingProgramparts[0];
            }

            if (!empty($product->getIncstoresPimShippingProgram()) && $shippingProgram != 'none' &&
                $shippingProgram != 'free_ship') {
                $pdp_quick_ship = 1;
            }

            $pdpQuickShipArr[$product->getId()] = $pdp_quick_ship;
        }

        $storeId = $this->storeManager->getDefaultStoreView()->getId();
        $this->storeManager->setCurrentStore($storeId);

        return $pdpQuickShipArr;
    }

    public function getOveragePercent($subject)
    {
        $overagePercent = [];

        foreach ($subject->getAllowProducts() as $product) {
            $overage_percent = '';

            if (!empty($product->getIncstoresPimPercentOverage())) {
                $overage_percent = $product->getIncstoresPimPercentOverage();
            }

            $overagePercent[$product->getId()] = $overage_percent;
        }

        return $overagePercent;
    }
}
