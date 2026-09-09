<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dcw\ProductImage\Model\Cart;

use Magento\Checkout\CustomerData\DefaultItem;
use Magento\Framework\App\ObjectManager;

/**
 * @api
 * @since 100.0.2
 */
class ImageProvider extends \Magento\Checkout\Model\Cart\ImageProvider
{
    /**
     * @var \Magento\Quote\Api\CartItemRepositoryInterface
     */
    protected $itemRepository;

    /**
     * @var \Magento\Checkout\CustomerData\ItemPoolInterface
     * @deprecated 100.2.7 No need for the pool as images are resolved in the default item implementation
     * @see \Magento\Checkout\CustomerData\DefaultItem::getProductForThumbnail
     */
    protected $itemPool;

    /**
     * @var \Magento\Checkout\CustomerData\DefaultItem
     * @since 100.2.7
     */
    protected $customerDataItem;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    private $imageHelper;

    /**
     * @var \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface
     */
    private $itemResolver;

    /**
     * @param \Magento\Quote\Api\CartItemRepositoryInterface $itemRepository
     * @param \Magento\Checkout\CustomerData\ItemPoolInterface $itemPool
     * @param DefaultItem|null $customerDataItem
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface $itemResolver
     */

    protected $productrepository; 
	protected $dcwimagehelper;
    public function __construct(
        \Magento\Quote\Api\CartItemRepositoryInterface $itemRepository,
        \Magento\Checkout\CustomerData\ItemPoolInterface $itemPool,
        \Magento\Checkout\CustomerData\DefaultItem $customerDataItem = null,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface $itemResolver = null,
        \Magento\Catalog\Api\ProductRepositoryInterface $productrepository,
		\Dcw\ProductImage\Helper\Data $dcwimagehelper,
		
    ) {
        parent::__construct(
        $itemRepository,
        $itemPool,
        $customerDataItem = null,
        $imageHelper,
        $itemResolver = null
        );
        $this->imageHelper = $imageHelper;
        $this->productrepository = $productrepository;
		$this->dcwimagehelper = $dcwimagehelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getImages($cartId)
    {
        $itemData = [];

        /** @see code/Magento/Catalog/Helper/Product.php */
        $items = $this->itemRepository->getList($cartId);
        /** @var \Magento\Quote\Model\Quote\Item $cartItem */
        foreach ($items as $cartItem) {
            $itemData[$cartItem->getItemId()] = $this->getProductImageData($cartItem);
        }
        return $itemData;
    }

    /**
     * Get product image data
     *
     * @param \Magento\Quote\Model\Quote\Item $cartItem
     *
     * @return array
     */
    private function getProductImageData($cartItem)
    {
        $productType = $cartItem->getProductType();
        if($productType=='configurable'){
            $productId = $cartItem->getProduct()->getCustomOption('simple_product')->getProductId();
        } else {
            $productId = $cartItem->getData('product_id');;
        }
        $productDetail = $this->productrepository->getById($productId);
		$imagesdimensionmedium = $this->dcwimagehelper->getThumbnailImageDimension();
		$proimage = $this->dcwimagehelper->getMainImageUrl($productDetail);
        $sampleUrl = '';
        $productOptions = $cartItem->getOptionByCode('additional_options');
        if ($productOptions) {
            $additionalOptions = json_decode($productOptions->getValue(), true);  
            if (is_array($additionalOptions)) {
                
                foreach ($additionalOptions as $key => $option) {
                    
                    if($key==="configurable_product_image") {
                    $sampleUrl = $option['value'];
                    }
                   
                }
            }
        }
        if($sampleUrl!==''){
            $productImage = $sampleUrl;
        } else {
        $productImage = ($proimage !== "") ? $proimage.'/-B'.$imagesdimensionmedium : ($this->imageHelper->getDefaultPlaceholderUrl('thumbnail'));
        }
        $imageData = [
            'src' => $productImage,
            'alt' => $productDetail->getData('name'),
        ];
        return $imageData;
    }
}