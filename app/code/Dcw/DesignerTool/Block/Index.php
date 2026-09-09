<?php

namespace Dcw\DesignerTool\Block;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Swatches\Model\ResourceModel\Swatch\Collection as SwatchCollection;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class Index extends \Magento\Framework\View\Element\Template
{
    protected $_coreRegistry;
    protected $swatchCollection;
    protected $productRepository;
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Dir $moduleDir,
        \Magento\Swatches\Helper\Media $swatchHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Dcw\DesignerTool\Helper\Data $helperData,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        AttributeRepositoryInterface $attributeRepository,
        SwatchCollection $swatchCollection,
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_productloader = $_productloader;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->moduleDir = $moduleDir;
        $this->swatchHelper = $swatchHelper;
        $this->objectManager = $objectManager;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->helperData = $helperData;
        $this->imageHelper = $imageHelper;
        $this->cookieManager = $cookieManager;
        $this->attributeRepository = $attributeRepository;
        $this->swatchCollection = $swatchCollection;
        $this->productRepository = $productRepository;

        parent::__construct($context, $data);
    }
    
    public function getProductById($productId)
    {
        try {
            return $this->_productloader->create()->load($productId);
        } catch (NoSuchEntityException $e) {
            // Handle case where product is not found
            return null;
        }
    }

    public function getProductBySku($productSku)
    {
        try {
            return $this->productRepository->get($productSku); // Load product by SKU
        } catch (NoSuchEntityException $e) {
            // Handle case where product is not found
            return null;
        }
    }
        
    public function getConfigValue($store_config_field)
    {
        return $this->scopeConfig->getValue(
            $store_config_field,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getStoreId()
        );
    }
        
    public function getMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }
    
    public function moduleViewPath()
    {
        return $this->moduleDir->getDir('Dcw_DesignerTool', \Magento\Framework\Module\Dir::MODULE_VIEW_DIR);
    }

    public function designerToolHelper()
    {
        return $this->helperData;
    }

    public function imageHelper()
    {
        return $this->imageHelper;
    }

    public function getCookieValue($cName)
    {
        return $this->cookieManager->getCookie($cName);
    }

    public function getDefaultPatternTile($product_id, $selectedChildId)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $product = $this->getProductById($product_id);
        $selectedChildId = $this->getProductById($selectedChildId);
        $_children = $product->getTypeInstance()->getUsedProducts($product);
        $defaultPatternTileArr = [];

        foreach ($_children as $_child_product) {
            if ($_child_product->getdefaultDesignTile() == 1 &&
            (($_child_product->getIncstoresPimExactWidthInches() == $selectedChildId->getIncstoresPimExactWidthInches()) &&
            ($_child_product->getIncstoresPimExactLengthInches() == $selectedChildId->getIncstoresPimExactLengthInches()))) {
                $swatchCollection = $this->objectManager
                ->create(\Magento\Swatches\Model\ResourceModel\Swatch\Collection::class);
                if (!empty($_child_product->getIncstoresPimColorAxis())) {
                    $optionIdvalue = $_child_product->getIncstoresPimColorAxis();
                    $swatchCollection->addStoreFilter($storeId)->addFieldtoFilter('option_id', $optionIdvalue);
                    $item = $swatchCollection->getFirstItem();
                    $defaultPatternTileArr = $item->getData();
                    if (!empty($_child_product->getIncstoresPimSwatchImageSkuUrl1())) {
                        $defaultPatternTileArr['swatch_image'] = $_child_product->getIncstoresPimSwatchImageSkuUrl1();
                    } else {
                        $defaultPatternTileArr['swatch_image']
                        = $this->imageHelper->getDefaultPlaceholderUrl('small_image');
                    }
                    $defaultPatternTileArr['color_title']
                    = $_child_product->getAttributeText('incstores_pim_color_axis');
                    $defaultPatternTileArr['color_type'] = 'swatch_color_url';
                    $defaultPatternTileArr['product_id']
                    = $_child_product->getId();
                    $defaultPatternTileArr['product_final_price']
                    = $_child_product->getFinalPrice(1);
                    $defaultPatternTileArr['product_regular_price']
                    = $_child_product->getPrice();

                    break;
                }
            }
        }
        return $defaultPatternTileArr;
    }
        
    public function getColorItems($product_id, $selectedChildId)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $product = $this->getProductById($product_id);
        $selectedChildId = $this->getProductById($selectedChildId);
        $_children = $product->getTypeInstance()->getUsedProducts($product);
        $colorItemOptionsArr = [];
        foreach ($_children as $_child_product) {
            if (($_child_product->getIncstoresPimExactWidthInches() == $selectedChildId->getIncstoresPimExactWidthInches()) &&
            ($_child_product->getIncstoresPimExactLengthInches() == $selectedChildId->getIncstoresPimExactLengthInches())) {
                $swatchCollection = $this->objectManager
                ->create(\Magento\Swatches\Model\ResourceModel\Swatch\Collection::class);

                if (!empty($_child_product->getIncstoresPimColorAxis())) {
                    $optionIdvalue = $_child_product->getIncstoresPimColorAxis();
                    $swatchCollection->addStoreFilter($storeId)->addFieldtoFilter('option_id', $optionIdvalue);
                    $item = $swatchCollection->getFirstItem();
                    $attributelabel = $_child_product->getAttributeText('incstores_pim_color_axis');
                    $colorItemOptionsArr[$attributelabel] = $item->getData();

                    if (!empty($_child_product->getIncstoresPimSwatchImageSkuUrl1())) {
                        $colorItemOptionsArr[$attributelabel]['swatch_image'] = $_child_product->getIncstoresPimSwatchImageSkuUrl1();
                    } else {
                        $colorItemOptionsArr[$attributelabel]['swatch_image']
                        = $this->imageHelper->getDefaultPlaceholderUrl('small_image');
                    }
                   
                    $colorItemOptionsArr[$attributelabel]['color_title']
                    = $optionIdvalue;
                    $colorItemOptionsArr[$attributelabel]['product_id']
                    = $_child_product->getId();
                    $colorItemOptionsArr[$attributelabel]['product_final_price']
                    = $_child_product->getFinalPrice(1);
                    $colorItemOptionsArr[$attributelabel]['product_regular_price']
                    = $_child_product->getPrice();

                    $colorItemOptionsArr[$attributelabel]['child_id']
                    = $_child_product->getId();
                }
            }

        }
            
        return $colorItemOptionsArr;
    }

    public function getVariantColorCombination($product_id, $selectedChildId)
    {
        $swatchCollection = $this->swatchCollection;

        $storeId = $this->storeManager->getStore()->getId();
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $product = $this->getProductById($product_id);
        $selectedChildId = $this->getProductById($selectedChildId);
        $_children = $product->getTypeInstance()->getUsedProducts($product);
        $variantItemOptionsArr = [];

        $configAttributesArray = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);

        //filter so only color is left and select the values in the array
        $configAttributesFilteredByColor = array_filter($configAttributesArray, function($v) { return $v['attribute_code'] == 'incstores_pim_variant_axis'; });
        $configAttributesFilteredByColorValues = $configAttributesFilteredByColor[key($configAttributesFilteredByColor)]['values'];

        //loop the values, and the childproducts and match them
        foreach($configAttributesFilteredByColorValues as $configAttributeValue) {
            $value = $configAttributeValue['value_index'];

            foreach ($_children as $_child_product) {
                if (($_child_product->getIncstoresPimExactWidthInches() == $selectedChildId->getIncstoresPimExactWidthInches()) &&
                ($_child_product->getIncstoresPimExactLengthInches() == $selectedChildId->getIncstoresPimExactLengthInches())) {
                    if (!empty($_child_product->getData('incstores_pim_variant_axis'))) {
                        $optionIdvalue = $_child_product->getData('incstores_pim_color_axis');

                        $swatchCollection->addStoreFilter($storeId)->addFieldtoFilter('option_id', $optionIdvalue);
                        $item = $swatchCollection->getFirstItem();

                        $attributelabel = $_child_product->getAttributeText('incstores_pim_color_axis');
                        
                        $colorAttribute = $_child_product->getCustomAttribute('incstores_pim_color_axis');

                        // Load the attribute to get the label
                        $eavAttributeColor = $this->attributeRepository->get('catalog_product', 'incstores_pim_color_axis');
                        $eavAttributeVariant = $this->attributeRepository->get('catalog_product', 'incstores_pim_variant_axis');
                        $colorOptionId = $colorAttribute->getValue();
                        $optionLabel = $eavAttributeColor->getSource()->getOptionText($colorOptionId);

                        $variantItemOptionsArr[] = $value.'_'.$colorOptionId;
                    }
                }
            }
        }

        return array_values(array_unique($variantItemOptionsArr));
    }

    public function getVariantItems($product_id, $selectedChildId)
    {
        $swatchCollection = $this->swatchCollection;

        $storeId = $this->storeManager->getStore()->getId();
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $product = $this->getProductById($product_id);
        $selectedChildId = $this->getProductById($selectedChildId);
        $_children = $product->getTypeInstance()->getUsedProducts($product);
        $variantItemOptionsArr = [];

        $configAttributesArray = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);

        //filter so only color is left and select the values in the array
        $configAttributesFilteredByColor = array_filter($configAttributesArray, function($v) { return $v['attribute_code'] == 'incstores_pim_variant_axis'; });
        $configAttributesFilteredByColorValues = $configAttributesFilteredByColor[key($configAttributesFilteredByColor)]['values'];

        //loop the values, and the childproducts and match them
        foreach($configAttributesFilteredByColorValues as $configAttributeValue) {
            $value = $configAttributeValue['value_index'];

            foreach ($_children as $_child_product) {
                if (($_child_product->getIncstoresPimExactWidthInches() == $selectedChildId->getIncstoresPimExactWidthInches()) &&
                ($_child_product->getIncstoresPimExactLengthInches() == $selectedChildId->getIncstoresPimExactLengthInches())) {
                    if (!empty($_child_product->getData('incstores_pim_variant_axis'))) {
                        $optionIdvalue = $_child_product->getData('incstores_pim_variant_axis');

                        $colorOptionIdvalue = $_child_product->getData('incstores_pim_color_axis');

                        $swatchCollection->addStoreFilter($storeId)->addFieldtoFilter('option_id', $optionIdvalue);
                        $item = $swatchCollection->getFirstItem();
                        

                        $variantItemOptionsArr[$_child_product->getAttributeText('incstores_pim_variant_axis')]['variant_title']
                        = $_child_product->getAttributeText('incstores_pim_variant_axis');
                        $variantItemOptionsArr[$_child_product->getAttributeText('incstores_pim_variant_axis')]['product_id']
                        = $_child_product->getId();

                        $variantItemOptionsArr[$_child_product->getAttributeText('incstores_pim_variant_axis')]['option_id']
                        = $optionIdvalue;

                        $colorAttribute = $_child_product->getCustomAttribute('incstores_pim_color_axis');

                        // Load the attribute to get the label
                        $eavAttributeColor = $this->attributeRepository->get('catalog_product', 'incstores_pim_color_axis');
                        $eavAttributeVariant = $this->attributeRepository->get('catalog_product', 'incstores_pim_variant_axis');
                        $colorOptionId = $colorAttribute->getValue();
                        $optionLabel = $eavAttributeColor->getSource()->getOptionText($colorOptionId);

                        if ($_child_product->getData('incstores_pim_variant_axis') == $value) {
                            $getIncstoresPimSwatchImageSkuUrl1 = $_child_product->getIncstoresPimSwatchImageSkuUrl1();

                            $getIncstoresPimFdSwatchSkuUrl1 = $_child_product->getIncstoresPimFdSwatchSkuUrl1();

                            if (!empty($getIncstoresPimFdSwatchSkuUrl1)) {
                                $variantItemOptionsArr[$_child_product->getAttributeText('incstores_pim_variant_axis')]['swatch_image_label'][]
                                = $getIncstoresPimFdSwatchSkuUrl1.",".$optionLabel.",".$colorOptionIdvalue.",".$_child_product->getId();
                            } else if (!empty($getIncstoresPimSwatchImageSkuUrl1)) {
                                $variantItemOptionsArr[$_child_product->getAttributeText('incstores_pim_variant_axis')]['swatch_image_label'][]
                                = $getIncstoresPimSwatchImageSkuUrl1.",".$optionLabel.",".$colorOptionIdvalue.",".$_child_product->getId();
                            } else {
                                $variantItemOptionsArr[$_child_product->getAttributeText('incstores_pim_variant_axis')]['swatch_image_label'][]
                                = $this->imageHelper->getDefaultPlaceholderUrl('small_image').",".$optionLabel.",".$colorOptionIdvalue.",".$_child_product->getId();
                            }

                            $variantItemOptionsArr[$_child_product->getAttributeText('incstores_pim_variant_axis')]['option_id']
                            = $optionIdvalue;

                            $variantItemOptionsArr[$_child_product->getAttributeText('incstores_pim_variant_axis')]['color_option_id'][] = $colorOptionId;

                            $variantItemOptionsArr[$_child_product->getAttributeText('incstores_pim_variant_axis')]['product_final_price'][]
                            = $_child_product->getFinalPrice(1);
                            $variantItemOptionsArr[$_child_product->getAttributeText('incstores_pim_variant_axis')]['product_regular_price'][]
                            = $_child_product->getPrice();
                        }
                    }
                }
            }
        }

        return $variantItemOptionsArr;
    }

    /**
     * get child product list from the sku
     */
    public function getAllChildProductsListBySku($productSku)
    {
        $product = $this->getProductBySku($productSku); // Load product by SKU
        $childProductsList = [];

        if ($product && $product->getTypeId() === Configurable::TYPE_CODE) {
            $typeInstance = $product->getTypeInstance();
            $usedProducts = $typeInstance->getUsedProducts($product);

            foreach ($usedProducts as $childProduct) {
                // $childProduct is the child product of the configurable
                $childProductsList[] = $childProduct->getSku();
            }
        }

        return $childProductsList;
    }

    /**
     * get the child products from the configurable product id
     */
    public function getAllChildProductsList($productId)
    {
        $product = $this->getProductById($productId);
        $_children = $product->getTypeInstance()->getUsedProducts($product);
        $childProductsList = [];

        foreach ($_children as $_child_product) {
            if (!empty($_child_product->getData('incstores_pim_variant_axis'))) {
                $childProductsList[] = $_child_product->getId();
            }
        }

        return $childProductsList;
    }

    public function makeUniqueSkus($skuString)
    {
        $skus = [];

        if ($skuString) {
            // Split the combined string into an array
            $skus = explode(',', $skuString);

            // Remove empty values from the array
            $skus = array_filter($skus);

            // Remove duplicate values
            $skus = array_unique($skus);

            // Re-index the array (optional, but recommended)
            $skus = array_values($skus);
        }

        return $skus;
    }

    public function getFinalSkusList($skusList)
    {
        $skus = $this->makeUniqueSkus($skusList);

        $childList = [];

        foreach ($skus as $skuItem) {
            $childList[] = $this->getAllChildProductsListBySku($skuItem);
        }

        $childListString = "";

        foreach ($childList as $child) {
            // $child is an array, so you loop through it again
            foreach ($child as $value) {
                $childListString.= $value . ',';
            }
        }

        return $this->makeUniqueSkus($childListString);
    }

    /**
     * get all product edge options as html
     */
    public function getAllEdgingOptions($productId)
    {
        $getAllChildProductsList = $this->getAllChildProductsList($productId);

        $edgeHtmlArray = [];

        if (count($getAllChildProductsList) > 0) {
            foreach ($getAllChildProductsList as $getAllChildProductsListItems) {
                $edgeHtmlArray[$getAllChildProductsListItems] = $this->buildEdgingItemsHtml($getAllChildProductsListItems);
            }
        }

        return $edgeHtmlArray;
    }

    /**
     * get the edge items based on the product id
     */
    public function getEdgingItemsBasedOnProductId($productId)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $product = $this->getProductById($productId);

        $maleEdgingSkus = $product->getData('incstores_pim_fd_edge_male');
        $femaleEdgingSkus = $product->getData('incstores_pim_fd_edge_female');
        $universalEdgingSkus = $product->getData('incstores_pim_fd_edge_universal');

        $skus = [];
        $edgingItemOptionsArray = [];
        $edgingFemaleItemOptionsArr = [];
        $edgingMaleItemOptionsArr = [];

        if ($universalEdgingSkus) {
            $maleEdgingSkus = $femaleEdgingSkus = $universalEdgingSkus;
        }
        
        if ($maleEdgingSkus) {
            $skus = $this->getFinalSkusList($maleEdgingSkus);

            // Filter the collection by SKUs
            $edgingCollection = $this->productCollectionFactory->create();
            $edgingCollection->addAttributeToSelect('*');
            $edgingCollection->addFieldToFilter('sku', ['in' => $skus]);
            $edgingCollection->addAttributeToFilter('status', 1);

            foreach ($edgingCollection as $_product) {
                if (!empty($_product->getIncstoresPimColorAxis())) {
                    $optionIdvalue = $product->getIncstoresPimColorAxis();

                    $variantOptionIdvalue = $product->getData('incstores_pim_variant_axis');

                    $this->swatchCollection->addStoreFilter($storeId)->addFieldtoFilter('option_id', $optionIdvalue);
                    $item = $this->swatchCollection->getFirstItem();
                    
                    $edgingItemOptionsArray[$_product->getAttributeText('incstores_pim_color_axis')] = $item->getData();

                    $getIncstoresPimFdSwatchSkuUrl1 = $_product->getIncstoresPimFdSwatchSkuUrl1();

                    $edgingMaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')] = $item->getData();

                    if (!empty($getIncstoresPimFdSwatchSkuUrl1)) {
                        $edgingItemOptionsArray[$_product->getAttributeText('incstores_pim_color_axis')]['swatch_image'] = $getIncstoresPimFdSwatchSkuUrl1;
                    } else if (!empty($_product->getIncstoresPimSwatchImageSkuUrl1())) {
                        $edgingMaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['swatch_image'] = $_product->getIncstoresPimSwatchImageSkuUrl1();
                    } else {
                        $edgingMaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['swatch_image']
                        = $this->imageHelper->getDefaultPlaceholderUrl('small_image');
                    }
                    $edgingMaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['color_title']
                    = $_product->getAttributeText('incstores_pim_color_axis');
                    $edgingMaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['male_product_id']
                    = $_product->getId();
                    $edgingMaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['male_product_final_price']
                    = $_product->getFinalPrice(1);
                    $edgingMaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['male_product_regular_price']
                    = $_product->getPrice();
                    $edgingMaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['option_id_vaue']
                    = $optionIdvalue;
                    $edgingMaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['variant_option_id_vaue']
                    = $variantOptionIdvalue;

                    $isAttrExist = $_product->getResource()->getAttribute('incstores_pim_color_axis'); // Add here your attribute code
                    $optId = '';
                    if ($isAttrExist && $isAttrExist->usesSource()) {
                        $optId = $isAttrExist->getSource()->getOptionId($_product->getAttributeText('incstores_pim_color_axis'));
                    }

                    $edgingMaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['color_option_id']
                    = $optId;
                }
            }
        }

        if ($femaleEdgingSkus) {

            $skus = $this->getFinalSkusList($femaleEdgingSkus);
            
            // Filter the collection by SKUs
            $edgingCollection = $this->productCollectionFactory->create();
            $edgingCollection->addAttributeToSelect('*');
            $edgingCollection->addFieldToFilter('sku', ['in' => $skus]);
            $edgingCollection->addAttributeToFilter('status', 1);

            foreach ($edgingCollection as $_product) {
                if (!empty($_product->getIncstoresPimColorAxis())) {
                    $optionIdvalue = $_product->getIncstoresPimColorAxis();
                    $variantOptionIdvalue = $product->getData('incstores_pim_variant_axis');

                    $this->swatchCollection->addStoreFilter($storeId)->addFieldtoFilter('option_id', $optionIdvalue);
                    $item = $this->swatchCollection->getFirstItem();
                    
                    $edgingItemOptionsArray[$_product->getAttributeText('incstores_pim_color_axis')] = $item->getData();

                    $getIncstoresPimFdSwatchSkuUrl1 = $_product->getIncstoresPimFdSwatchSkuUrl1();

                    $edgingFemaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')] = $item->getData();

                    if (!empty($getIncstoresPimFdSwatchSkuUrl1)) {
                        $edgingItemOptionsArray[$_product->getAttributeText('incstores_pim_color_axis')]['swatch_image'] = $getIncstoresPimFdSwatchSkuUrl1;
                    } else if (!empty($_product->getIncstoresPimSwatchImageSkuUrl1())) {
                        $edgingFemaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['swatch_image'] = $_product->getIncstoresPimSwatchImageSkuUrl1();
                    } else {
                        $edgingFemaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['swatch_image']
                        = $this->imageHelper->getDefaultPlaceholderUrl('small_image');
                    }
                    
                    $edgingFemaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['color_title']
                    = $_product->getAttributeText('incstores_pim_color_axis');
                    $edgingFemaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['female_product_id']
                    = $_product->getId();
                    $edgingFemaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['female_product_final_price']
                    = $_product->getFinalPrice(1);
                    $edgingFemaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['female_product_regular_price']
                    = $_product->getPrice();
                    $edgingFemaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['option_id_vaue']
                    = $optionIdvalue;
                    $edgingFemaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['variant_option_id_vaue']
                    = $variantOptionIdvalue;

                    $isAttrExist = $_product->getResource()->getAttribute('incstores_pim_color_axis'); // Add here your attribute code
                    $optId = '';
                    if ($isAttrExist && $isAttrExist->usesSource()) {
                        $optId = $isAttrExist->getSource()->getOptionId($_product->getAttributeText('incstores_pim_color_axis'));
                    }

                    $edgingFemaleItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['color_option_id']
                    = $optId;
                }
            }
        }
        
        $edgingItemOptionsArray = [];

        if (count($edgingFemaleItemOptionsArr) > 0) {
            foreach ($edgingFemaleItemOptionsArr as $color_key => $edgingFemaleItemOptions) {
                foreach ($edgingFemaleItemOptions as $option => $edgingFemaleItemOption) {
                    $edgingItemOptionsArray[$color_key][$option] = $edgingFemaleItemOption;
                }
            }
        }

        if (count($edgingMaleItemOptionsArr) > 0) {
            foreach ($edgingMaleItemOptionsArr as $color_key => $edgingMaleItemOptions) {
                foreach ($edgingMaleItemOptions as $option => $edgingMaleItemOption) {
                    $edgingItemOptionsArray[$color_key][$option] = $edgingMaleItemOption;
                }
            }
        }

        return $edgingItemOptionsArray;
    }

    /**
     * based on the simple product id build the Edges html
     */
    public function buildEdgingItemsHtml($productId)
    {
        $edgingItemOptionHtml = "";
        $edgingItemOptionsArray = $this->getEdgingItemsBasedOnProductId($productId);
        $i = 1;

        foreach ($edgingItemOptionsArray as $edgingItemOption) {

            $activeClass = $checked = "";

            if ($i == 1) {
                $activeClass = "edge_active";
                $checked = "checked";
            }

            $color_type = 'swatch_color_url';
            $design_tiles_color = $edgingItemOption['swatch_image'];

            $edgeOptionId = $edgingItemOption['option_id_vaue'];
            $edgeVariantOptionId = $edgingItemOption['variant_option_id_vaue'];
            $edgeSwatchImage = $edgingItemOption['swatch_image'];
            $edgeVaule = $edgingItemOption['value'];
            $edgeColorTitle = $edgingItemOption['color_title'];
            $edgeMaleProductId = $edgingItemOption['male_product_id'] ?? '';
            $edgeColorOptionId = $edgingItemOption['color_option_id'];
            $edgeFemaleProductId = $edgingItemOption['female_product_id'] ?? '';

            $edgeFemaleProductFinalPrice = $edgingItemOption['female_product_final_price'] ?? '';
            $edgeFemaleProductRegularPrice = $edgingItemOption['female_product_regular_price'] ?? '';
            $edgeMaleProductFinalPrice = $edgingItemOption['male_product_final_price'] ?? '';
            $edgeMaleProductRegularPrice = $edgingItemOption['male_product_regular_price'] ?? '';

            if (!empty($edgeMaleProductId) || !empty($edgeFemaleProductId)) {
                $edgingItemOptionHtml.='<li class="edge_color_list edge_color_list_'.$productId.'" style="display:none;">
                    <input type="radio" name="edge_color" class="edge_color_radio edge_main_'.$productId.'" 
                        id="edge_color_'.$edgeColorOptionId.'_'.$productId.'"
                        data-swatch_image="'.$edgeSwatchImage.'"
                        data-color_type="'.$color_type.'"
                        value="'.$edgeVaule.'"
                        data-color_title="'.$edgeColorTitle.'"
                        data-color_option_id="'.$edgeColorOptionId.'"
                        data-main_product_id="'.$productId.'"
                        data-male_product_id="'.$edgeMaleProductId.'"
                        data-female_product_id="'.$edgeFemaleProductId.'" '.$checked.'

                        data-female_product_final_price="'.$edgeFemaleProductFinalPrice.'"
                        data-female_product_regular_price="'.$edgeFemaleProductRegularPrice.'"
                        data-male_product_final_price="'.$edgeMaleProductFinalPrice.'"
                        data-male_product_regular_price="'.$edgeMaleProductRegularPrice.'"
                        />
                    <label class="edge_color cursor-pointer '.$activeClass.'" 
                    for="edge_color_'.$edgeVariantOptionId.'_'.$edgeOptionId.'" >';
                        if (!empty($edgeSwatchImage)) {
                            $edgingItemOptionHtml.='<img src="'.$edgeSwatchImage.'"
                            title="'.$edgeColorTitle.'" 
                            alt="'.$edgeColorTitle.'" class="edge_color_image"/>';
                        }
                        $edgingItemOptionHtml.='<div class="color_title text-size14 font-medium text-custom-white ">
                            '.$edgeColorTitle.'
                        </div>
                    </label>
                </li>';
            }
            $i++;
        }

        return $edgingItemOptionHtml;
    }

    /**
     * get all product corner options as html
     */
    public function getAllCornerOptions($productId)
    {
        $getAllChildProductsList = $this->getAllChildProductsList($productId);

        $cornerHtmlArray = [];

        if (count($getAllChildProductsList) > 0) {
            foreach ($getAllChildProductsList as $getAllChildProductsListItems) {
                $cornerHtmlArray[$getAllChildProductsListItems] = $this->buildCornerItemsHtml($getAllChildProductsListItems);
            }
        }

        return $cornerHtmlArray;
    }

    /**
     * get the corner items based on the product id
     */
    public function getCornerItemsBasedOnProductId($productId)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $product = $this->getProductById($productId);

        $incstores_pim_fd_corner_universal = $product->getData('incstores_pim_fd_corner_universal');
        $incstores_pim_fd_corner_edge_double_universal = $product->getData('incstores_pim_fd_corner_edge_double_universal');
        $incstores_pim_fd_corner_edge_single_universal = $product->getData('incstores_pim_fd_corner_edge_single_universal');
        $incstores_pim_fd_corner_edge_single_male = $product->getData('incstores_pim_fd_corner_edge_single_male');
        $incstores_pim_fd_corner_edge_single_female = $product->getData('incstores_pim_fd_corner_edge_single_female');
        $cornerSkus = $cornerType = $cornerSingleMaleSkus = $cornerSingleFemaleSkus = "";
        $cornerItemOptionsArray = [];

        if ($incstores_pim_fd_corner_edge_single_universal) {
            $cornerSkus = $incstores_pim_fd_corner_edge_single_universal;
            $cornerType = "singleuniversal";
        }

        if ($incstores_pim_fd_corner_edge_double_universal) {
            $cornerSkus = $incstores_pim_fd_corner_edge_double_universal;
            $cornerType = "doubleuniversal";
        }

        if ($incstores_pim_fd_corner_universal) {
            $cornerSkus = $incstores_pim_fd_corner_universal;
            $cornerType = "corneruniversal";
        }

        if ($incstores_pim_fd_corner_edge_single_male) {
            $cornerSingleMaleSkus = $incstores_pim_fd_corner_edge_single_male;
        }

        if ($incstores_pim_fd_corner_edge_single_female) {
            $cornerSingleFemaleSkus = $incstores_pim_fd_corner_edge_single_female;
        }

        if ($cornerSingleMaleSkus || $cornerSingleFemaleSkus) {
            if (!empty($cornerSingleMaleSkus) && !empty($cornerSingleFemaleSkus)) {
                $cornerSkus = $cornerSingleMaleSkus . "," . $cornerSingleFemaleSkus;
            } else if (empty($cornerSingleMaleSkus) && !empty($cornerSingleFemaleSkus)) {
                $cornerSkus = $cornerSingleFemaleSkus;
            } else {
                $cornerSkus = $cornerSingleMaleSkus;
            }

            $cornerType = "corneruniversal";
        }

        if ($cornerSkus) {
            $skus = $this->getFinalSkusList($cornerSkus);
            
            // Filter the collection by SKUs
            $cornerCollection = $this->productCollectionFactory->create();
            $cornerCollection->addAttributeToSelect('*');
            $cornerCollection->addFieldToFilter('sku', ['in' => $skus]);
            $cornerCollection->addAttributeToFilter('status', 1);

            $swatchCollection = $this->swatchCollection;

            foreach ($cornerCollection as $_product) {
                if (!empty($_product->getIncstoresPimColorAxis())) {
                    $optionIdvalue = $_product->getIncstoresPimColorAxis();
                    $variantOptionIdvalue = $product->getData('incstores_pim_variant_axis');

                    $swatchCollection->addStoreFilter($storeId)->addFieldtoFilter('option_id', $optionIdvalue);
                    $item = $swatchCollection->getFirstItem();
                    
                    $cornerItemOptionsArray[$_product->getAttributeText('incstores_pim_color_axis')] = $item->getData();
                    if (!empty($_product->getIncstoresPimSwatchImageSkuUrl1())) {
                        $cornerItemOptionsArray[$_product->getAttributeText('incstores_pim_color_axis')]['swatch_image'] = $_product->getIncstoresPimSwatchImageSkuUrl1();
                    } else {
                        $cornerItemOptionsArray[$_product->getAttributeText('incstores_pim_color_axis')]['swatch_image']
                        = $this->imageHelper->getDefaultPlaceholderUrl('small_image');
                    }
                    $cornerItemOptionsArray[$_product->getAttributeText('incstores_pim_color_axis')]['color_title']
                    = $_product->getAttributeText('incstores_pim_color_axis');
                    $cornerItemOptionsArray[$_product->getAttributeText('incstores_pim_color_axis')]['product_id'] = $_product->getId();
                    $cornerItemOptionsArray[$_product->getAttributeText('incstores_pim_color_axis')]['product_final_price']
                    = $_product->getFinalPrice(1);
                    $cornerItemOptionsArray[$_product->getAttributeText('incstores_pim_color_axis')]['product_regular_price']
                    = $_product->getPrice();

                    $cornerItemOptionsArray[$_product->getAttributeText('incstores_pim_color_axis')]['variant_option_id_vaue']
                    = $variantOptionIdvalue;

                    $isAttrExist = $_product->getResource()->getAttribute('incstores_pim_color_axis'); // Add here your attribute code
                    $optId = '';
                    if ($isAttrExist && $isAttrExist->usesSource()) {
                        $optId = $isAttrExist->getSource()->getOptionId($_product->getAttributeText('incstores_pim_color_axis'));
                    }

                    $cornerItemOptionsArray[$_product->getAttributeText('incstores_pim_color_axis')]['color_option_id']
                    = $optId;

                    $cornerItemOptionsArray[$_product->getAttributeText('incstores_pim_color_axis')]['option_id_vaue']
                    = $optionIdvalue;

                    $cornerItemOptionsArray[$_product->getAttributeText('incstores_pim_color_axis')]['corner_type']
                    = $cornerType;
                }
            }
        }

        return $cornerItemOptionsArray;
    }

    /**
     * based on the simple product id build the Corner html
     */
    public function buildCornerItemsHtml($productId)
    {
        $cornerItemOptionHtml = "";
        $cornerItemOptionsArray = $this->getCornerItemsBasedOnProductId($productId);
        $i = 1;

        foreach ($cornerItemOptionsArray as $cornerItemOption) {

            $activeClass = $checked = "";

            if ($i == 1) {
                $activeClass = "corner_active";
                $checked = "checked";
            }
            
            $color_type = 'swatch_color_url';

            $cornerOptionId = $cornerItemOption['option_id_vaue'];
            $cornerVariantOptionId = $cornerItemOption['variant_option_id_vaue'];
            $cornerSwatchImage = $cornerItemOption['swatch_image'];
            $cornerVaule = $cornerItemOption['value'];
            $cornerColorTitle = $cornerItemOption['color_title'];
            $cornerColorOptionId = $cornerItemOption['color_option_id'];
            $cornerProductFinalPrice = $cornerItemOption['product_final_price'];
            $cornerProductRegularPrice = $cornerItemOption['product_regular_price'];
            $cornerMainProductId = $cornerItemOption['product_id'];
            $cornerType = $cornerItemOption['corner_type'];

            if (!empty($cornerSwatchImage)) {
                $cornerItemOptionHtml.='<li class="corner_color_list corner_color_list_'.$productId.'" style="display:none;">
                    <input type="radio" name="corner_color" class="corner_color_radio corner_main_'.$productId.'" 
                        id="corner_color_'.$cornerColorOptionId.'_'.$productId.'"
                        data-swatch_image="'.$cornerSwatchImage.'"
                        data-color_type="'.$color_type.'"
                        value="'.$cornerVaule.'"
                        data-color_title="'.$cornerColorTitle.'"
                        data-color_option_id="'.$cornerColorOptionId.'"
                        data-main_product_id="'.$cornerMainProductId.'"
                        data-product_id="'.$productId.'"
                        data-product_final_price="'.$cornerProductFinalPrice.'"
                        data-product_regular_price="'.$cornerProductRegularPrice.'"
                        data-corner_type="'.$cornerType.'"
                        '.$checked.'
                        />
                    <label class="corner_color cursor-pointer '.$activeClass.'" 
                    for="corner_color_'.$cornerVariantOptionId.'_'.$cornerOptionId.'" >';
                        if (!empty($cornerSwatchImage)) {
                            $cornerItemOptionHtml.='<img src="'.$cornerSwatchImage.'"
                            title="'.$cornerColorTitle.'" 
                            alt="'.$cornerColorTitle.'" class="corner_color_image"/>';
                        }
                        $cornerItemOptionHtml.='<div class="color_title text-size14 font-medium text-custom-white ">
                            '.$cornerColorTitle.'
                        </div>
                    </label>
                </li>';
            }
            $i++;
        }

        return $cornerItemOptionHtml;
    }
    
    public function getCornerItems()
    {
        $swatchCollection = $this->swatchCollection;
        $storeId = $this->storeManager->getStore()->getId();
        
        $_products = $this->productCollectionFactory->create();
        $_products->addAttributeToSelect('*')
                    ->addAttributeToFilter('display_corner', 1)
                    ->addAttributeToFilter('status', 1);
          
        $cornerItemOptionsArr = [];
        
        foreach ($_products as $_product) {
            if (!empty($_product->getIncstoresPimColorAxis())) {
                $optionIdvalue = $_product->getIncstoresPimColorAxis();
                $swatchCollection->addStoreFilter($storeId)->addFieldtoFilter('option_id', $optionIdvalue);
                $item = $swatchCollection->getFirstItem();
                
                $cornerItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')] = $item->getData();
                if (!empty($_product->getIncstoresPimSwatchImageSkuUrl1())) {
                    $cornerItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['swatch_image'] = $_product->getIncstoresPimSwatchImageSkuUrl1();
                } else {
                    $cornerItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['swatch_image']
                    = $this->imageHelper->getDefaultPlaceholderUrl('small_image');
                }
                $cornerItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['color_title']
                = $_product->getAttributeText('incstores_pim_color_axis');
                $cornerItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['product_id'] = $_product->getId();
                $cornerItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['product_final_price']
                = $_product->getFinalPrice(1);
                $cornerItemOptionsArr[$_product->getAttributeText('incstores_pim_color_axis')]['product_regular_price']
                = $_product->getPrice();
            }
        }

        return $cornerItemOptionsArr;
    }

    public function isGarageShow($product_id)
    {
        $product = $this->getProductById($product_id);
        $categoryIds = $product->getCategoryIds();
        $garage_category_id = $this->getConfigValue('dcw_designer_tool/common_size/garage_category_id');
        $is_garage_show = 0;

        if (!empty($garage_category_id)) {
            $garage_category_idArr = explode(",",$garage_category_id);
            foreach($garage_category_idArr as $garage_cat_id) {
               if (in_array(trim($garage_cat_id),$categoryIds)){
                    
                    $is_garage_show = 1;
                    break;
               }
            }
        }

        return $is_garage_show;
    }
}
