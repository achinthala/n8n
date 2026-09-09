<?php
/**
 * Copyright © DCW, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @author DCW
 *
 */

declare(strict_types=1);

namespace Dcw\SampleProduct\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Model\Currency;
use Magento\Framework\Registry;
use Magento\Catalog\Helper\Image;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Dcw\ProductImage\Helper\Data as ProductImageHelper;
use Magento\Framework\Escaper;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\Pricing\Render;
use Magento\Catalog\Pricing\Price\FinalPrice;

class SampleData extends Template
{
    /**
     * @var \Magento\Framework\View\Element\Template\Context
     */
    protected $scopeConfig;
    
    /**
     * @var ProductFactory
     */
    protected $productloader;
    
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;
    
    /**
     * @var FormKey
     */
    protected $formKey;
    
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    
    /**
     * @var Currency
     */
    protected $currency;
    
    /**
     * @var Registry
     */
    protected $registry;
    
    /**
     * @var Image
     */
    protected $imagehelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ProductImageHelper
     */
    protected $productImageHelper;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param ObjectManagerInterface $objectManager
     * @param ProductFactory $productloader
     * @param FormKey $formKey
     * @param CheckoutSession $checkoutSession
     * @param Currency $currency
     * @param Registry $registry
     * @param Image $imagehelper
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $collectionFactory
     * @param ProductRepositoryInterface $productRepository
     */
        
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Swatches\Helper\Data $swatchHelper,
        ObjectManagerInterface $objectManager,
        ProductFactory $productloader,
        FormKey $formKey,
        CheckoutSession $checkoutSession,
        Currency $currency,
        Registry $registry,
        Image $imagehelper,
        StoreManagerInterface $storeManager,
        \Magento\Framework\Serialize\Serializer\Json $json,
        CollectionFactory $collectionFactory,
        ProductRepositoryInterface $productRepository,
        ProductImageHelper $productImageHelper,
        Escaper $escaper,
        LayoutInterface $layout
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->objectManager = $objectManager;
        $this->productloader = $productloader;
        $this->formKey = $formKey;
        $this->checkoutSession = $checkoutSession;
        $this->_currency = $currency;
        $this->_registry = $registry;
        $this->_imagehelper = $imagehelper;
        $this->swatchHelper = $swatchHelper;
        $this->storeManager = $storeManager;
        $this->_json = $json;
        $this->collectionFactory = $collectionFactory;
        $this->productRepository = $productRepository;
        $this->productImageHelper = $productImageHelper;
        $this->escaper = $escaper;
        $this->layout = $layout;

        parent::__construct($context);
    }
    
    /**
     * Get currency symbol for current locale and currency code
     *
     * @return string
     */
    public function getCurrentCurrencySymbol()
    {
        return $this->_currency->getCurrencySymbol();
    }

    /**
     * Get Hashcode of Visual swatch by option id
     *
     * @param $optionid
     *
     * @return array
     */
    public function getAtributeSwatchHashcode($optionid)
    {
        if ($optionid!="") {
            $hashcodeData = $this->swatchHelper->getSwatchesByOptionsId([$optionid]);
            return $hashcodeData[$optionid]['value'];
        }
    }
    
    /**
     * Get Quate product ids
     *
     * @return array
     */
    public function getQuoteProductIds()
    {
        $productids=[];
        $allItems =$this->checkoutSession->getQuote()->getAllItems();

        foreach ($allItems as $item) {
            $productids[] = $item->getProductId();
        }

        return $productids;
    }

    /**
     * Get quote product name
     *
     * @return array
     */
    public function getQuoteProductIdsName()
    {
        $productids = [];
        $optionValue = "";
        $allItems = $this->checkoutSession->getQuote()->getAllItems();

        foreach ($allItems as $item) {
            $options = $item->getOptionByCode('additional_options');

            if (isset($options) && !$options=== null) {
                $option_val = $this->_json->unserialize($options->getValue());
                if (isset($option_val) && !$option_val!="") {
                    foreach ($option_val as $op) {
                        if ($op['label'] == "Product Name") {
                            $optionValue = $op['value'];
                        }
                    }
                }
            }
    
            $pid = $item->getProductId();
            $productids[$pid] = $optionValue;
        }

        return $productids;
    }

    public function getQuoteProductIdsColor()
    {
        $productids=[];
        $optionValue="";
        $allItems =$this->checkoutSession->getQuote()->getAllItems();

        foreach ($allItems as $item) {
            $additionalOptions = $item->getOptionByCode('additional_options');
            
            if (!empty($additionalOptions)) {
                $option_val = $this->_json->unserialize($additionalOptions->getValue());
                if (isset($option_val) && $option_val!="") {
                    foreach ($option_val as $op) {
                        if ($op['label'] == "Color") {
                            $optionValue = $op['value'];
                            break;
                        }
                    }
                }
            }
            
            $pid = $item->getProductId();
            $productids[] = $pid.'~'.$optionValue;
        }

        return $productids;
    }

    public function getSampleProductColor($item)
    {
        $optionValue="";
       
        $additionalOptions = $item->getOptionByCode('additional_options');
        
        if (!empty($additionalOptions)) {
            $option_val = $this->_json->unserialize($additionalOptions->getValue());
            if (isset($option_val) && $option_val!="") {
                foreach ($option_val as $op) {
                    if ($op['label'] == "Color") {
                        $optionValue = $op['value'];
                        break;
                    }
                }
            }
        }
        
        return $optionValue;
    }

    public function getCartItemName($item)
    {
        $optionValue= $item->getName();
        $additionalOptions = $item->getOptionByCode('additional_options');
        
        if (!empty($additionalOptions) && $this->isSampleCartItem($item)) {
            $option_val = $this->_json->unserialize($additionalOptions->getValue());
            if (isset($option_val) && $option_val!="") {
                foreach ($option_val as $op) {
                    if ($op['label'] == "Product Name") {
                        $optionValue = $op['value'];
                        break;
                    }
                }
            }
        }
        
        return $optionValue;
    }

    public function getCartItemImage($item)
    {
        $optionValue= $item->getName();
        $additionalOptions = $item->getOptionByCode('additional_options');
        
        if (!empty($additionalOptions) && $this->isSampleCartItem($item)) {
            $option_val = $this->_json->unserialize($additionalOptions->getValue());
            if (isset($option_val) && $option_val!="") { 
                foreach ($option_val as $op) {
                    if ($op['label'] == "Configurable Product Image") {
                        $optionValue = $op['value'];
                        break;
                    }
                }
            }
        }
        
        return $optionValue;
    }

    public function isSampleCartItem($item)
    {
        $is_sampleCartItem = 0;
       
        $additionalOptions = $item->getOptionByCode('additional_options');
        
        if (!empty($additionalOptions)) {
            $option_val = $this->_json->unserialize($additionalOptions->getValue());
            if (!empty($option_val['sample_color'])) {
                $is_sampleCartItem = 1;
            }
        }
        
        return $is_sampleCartItem;
    }
    
    /**
     * Get Form Key
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }
    
    /**
     * Get currency symbol for current locale and currency code
     *
     * @param integer $id for load product
     */
    public function getLoadProduct($id)
    {
        try {
            return $this->productRepository->getById($id);
        } catch (NoSuchEntityException $e) {
            // Handle the case where the product doesn't exist
            return false;
        }
    }
    
    /**
     *  Get Product
     *
     * @param string $sku for load product
     *
     * @return \Magento\Catalog\Model\ProductFactory
     */
    public function getProductBySku($sku)
    {
        try {
            return $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            // Handle the case where the product doesn't exist
            return false;
        }
    }
    
    /**
     * Get currency symbol for current locale and currency code
     *
     * @param integer $id for its sample product
     *
     * @return array
     */
    public function getsampleproductsku($id)
    {
        $samplesku=[];
        $product=$this->getLoadProduct($id);

        if ($product) {
            if ($product->getSampleProductSku()) {
                $samplesku[]=$product->getSampleProductSku();
            }
            if ($product->getTypeId()=='configurable') {
                $_children=$product->getTypeInstance()->getUsedProducts($product);
                foreach ($_children as $child) {
                    if ($child->getSampleProductSku() && $child->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                        $samplesku[]=$child->getSampleProductSku();
                    }
                }
            }
        }
        
        return $samplesku;
    }
    
    /**
     * Send Media url
     *
     * @return string media url
     */
    public function getMediaDirectoryUrl()
    {
        $media_dir = $this->storeManager->getStore()
                   ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        return $media_dir;
    }
    
    /**
     * Get product image url
     *
     * @param \Magento\Catalog\Model\ProductFactory $_product
     * @param string $type for image type
     * @return string
     */
    
    public function getProductImageUrl($_product, $type)
    {
        return $this->_imagehelper->init($_product, $type)->getUrl();
    }
    
    /**
     * Get currency product id
     *
     * @return string
     */
    
    public function getCurrentProduct()
    {
        $product=$this->_registry->registry('current_product');
        if ($product) {
            return $product->getId();
        }
        return false;
    }

    /**
     * load products by product id's
     *
     * @param array $productIds
     */
    public function loadProductsByIds($productIds)
    {
        $productData = [];
        $productCollection = $this->collectionFactory->create()
            ->addAttributeToSelect(
                [
                    'entity_id',
                    'name',
                    'sku',
                    'incstores_pim_variant_axis',
                    'incstores_pim_color_axis',
                    'incstores_pim_swatch_image_sku_url_1',
                    'sample_product_sku'
                ]
            )
            ->addIdFilter($productIds)
            ->load();

        foreach ($productCollection as $product) {
            $productData[] = $product;
        }

        return $productData;
    }

    /**
     * build sample product data
     */
    public function buildSampleProductData($childIds)
    {
        $curSymbol = $this->getCurrentCurrencySymbol();
        $cantoImageHelper = $this->productImageHelper;
        $imageDcwHelper = $this->_imagehelper;

        $buildSampleProductData = [];
        $selectedproducts = [];

        $quotePids = $this->getQuoteProductIds();
        $quotePidsColor = $this->getQuoteProductIdsColor();
        
        if(is_array($quotePids) && count($quotePids) > 0) {
            $buildSampleProductData['spidsColor'] = "'".implode("','", $quotePidsColor)."'";
        }

        $loadProducts = $this->loadProductsByIds($childIds);

        if (is_array($loadProducts) && count($loadProducts) > 0) {
            foreach ($loadProducts as $productData) {
                $sampleSku = $productData->getSampleProductSku();

                if (isset($sampleSku) && !empty($sampleSku)) {
                    $variantAxis = $productData->getAttributeText('incstores_pim_variant_axis');
                    $colorAxis = $productData->getAttributeText('incstores_pim_color_axis');
                    $key = trim($variantAxis . ' ' . $colorAxis);

                    $buildSampleProductData['sampleIdArray'][$key] = $productData->getId();

                    
                    $loadSampleProduct = $this->getProductBySku($sampleSku);
                    // if the sample product is not found, continue to the next product
                    if (!$loadSampleProduct) {
                        continue;
                    }

                    if (in_array($loadSampleProduct->getId(), $quotePids)) {
                        $buildSampleProductData['selectedProducts'][] = $loadSampleProduct->getId();
                    }

                    $cantoImage = $productData->getIncstoresPimSwatchImageSkuUrl1();
                
                    if ($cantoImage != "") {
                        $imagesDimensionSmall = $cantoImageHelper->getThumbnailImageDimension();
                        $imagesDimensionMedium = $cantoImageHelper->getSmallImageDimension();
                        $smallImage = $cantoImage.'/-B'.$imagesDimensionSmall.'-FWEBP';
                        $mediumImage = $cantoImage.'/-B'.$imagesDimensionMedium.'-FWEBP';
                        $pdata['small'] = $smallImage;
                        $pdata['medium'] = $mediumImage;
                    } else {
                        $placeHolderImage = $imageDcwHelper->getDefaultPlaceholderUrl('thumbnail');
                        $pdata['small'] = $placeHolderImage;
                        $pdata['medium'] = $placeHolderImage;
                    }
                    // end canto image code
                    
                    $childprice = $loadSampleProduct->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
                    $pdata['childprice'] = '<span>'.$curSymbol.$childprice.'</span>';
                    $pdata['id'] = $loadSampleProduct->getId();
                    $pdata['child_id'] = $productData->getId();
                    $name = str_replace(array("'", '"'), ' ', $loadSampleProduct->getName());
                    $pdata['name'] = $this->escaper->escapeHtml(__($name));
                    $pdata['color'] = $productData->getData('incstores_pim_color_axis');
                    $colorName = $productData->getResource()->getAttribute('incstores_pim_color_axis')->getFrontend()->getValue($productData);;
                    $pdata['color_name'] = $colorName;
                    $pdata['color_variant_name'] = $productData->getAttributeText('incstores_pim_variant_axis').' '. $colorName;
                    
                    $buildSampleProductData['productdata'][] = $pdata;
                }
            }
        }


        

        return $buildSampleProductData;
    }

    /**
     * get main image data
     */
    public function getMainImageData($_product)
    {
        $cantoImageHelper = $this->productImageHelper;
        $imageDcwHelper = $this->_imagehelper;

        $cantoImage = $cantoImageHelper->getMainImageUrl($_product);

        if ($cantoImage != "") {
            $imagesDimensionMedium = $cantoImageHelper->getSmallImageDimension();
            $mainImageData = $cantoImage.'/-B'.$imagesDimensionMedium.'-FWEBP';
        } else {
            $mainImageData = $imageDcwHelper->getDefaultPlaceholderUrl('small_image');
        }

        return $mainImageData;
    }

    /**
     * render price html
     */
    public function renderPriceHtml($_product)
    {
        $pricehtml = '';
        $priceType = FinalPrice::PRICE_CODE;

        $arguments = [
            'include_container'     => true,
            'display_minimal_price' => true,
            'list_category_page'    => true,
            'zone'                  => Render::ZONE_ITEM_LIST,
        ];

        $priceRender = $this->layout->createBlock(
            Render::class,
            'product.price.render.default',
            ['data' => ['price_render_handle' => 'catalog_product_prices']]
        );

        if ($priceRender) {
            $pricehtml = $priceRender->render($priceType, $_product, $arguments);
        }

        return $pricehtml;
    }
}
