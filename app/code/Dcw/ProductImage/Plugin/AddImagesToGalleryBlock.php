<?php

declare(strict_types=1);

namespace Dcw\ProductImage\Plugin;

use Dcw\ProductImage\Helper\Data as ProductImageHelperData;
use Dcw\ProductImage\Model\DefaultChildGalleryResolver;
use Dcw\ProductImage\Model\ParentGalleryVideoProvider;
use Exception;
use Magento\Catalog\Block\Product\View\Gallery;
use Magento\Catalog\Model\Product;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\CollectionFactory;
use Magento\Framework\DataObject;
use Psr\Log\LoggerInterface;

class AddImagesToGalleryBlock
{
    public function __construct(
        private readonly CollectionFactory $dataCollectionFactory,
        private readonly ProductImageHelperData $imagehelper,
        private readonly DefaultChildGalleryResolver $defaultChildGalleryResolver,
        private readonly ParentGalleryVideoProvider $parentGalleryVideoProvider,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Use Canto URLs from the default child gallery (configurable PDP) in admin order.
     */
    public function afterGetGalleryImages(Gallery $subject, $images)
    {
        $images = $this->dataCollectionFactory->create();

        try {
            $product = $subject->getProduct();
            if (!$product instanceof Product) {
                return $images;
            }

            $galleryProduct = $this->defaultChildGalleryResolver->getGalleryProduct($product);
            $galleryItems = [];

            $galleryEntries = $this->imagehelper->getSortedGalleryEntries($galleryProduct);
            $productName = $galleryProduct->getName();
            $imagesdimensionmedium = $this->imagehelper->getThumbnailImageDimension();
            $imagesdimensionlarge = $this->imagehelper->getBaseImageDimension();

            foreach ($galleryEntries as $entry) {
                if ($entry['url'] === '' || $entry['url'] === 'required') {
                    continue;
                }

                $item = $entry['url'];
                $imageId = uniqid();
                $small = $item . '/-B' . $imagesdimensionmedium . '-FWEBP';
                $medium = $item . '/-B' . $imagesdimensionlarge . '-FWEBP';
                $large = $item . '/-B' . $imagesdimensionlarge . '-FWEBP';
                $galleryPosition = $entry['position'];

                $galleryItems[] = new DataObject([
                    'file' => $large,
                    'media_type' => 'image',
                    'value_id' => $imageId,
                    'row_id' => $imageId,
                    'label' => $productName,
                    'label_default' => $productName,
                    'position' => $galleryPosition,
                    'position_default' => $galleryPosition,
                    'disabled' => 0,
                    'url' => $large,
                    'path' => '',
                    'small_image_url' => $small,
                    'medium_image_url' => $medium,
                    'large_image_url' => $large,
                ]);
            }

            foreach ($this->parentGalleryVideoProvider->getEnrichedNonImageItems($galleryProduct) as $videoItem) {
                $galleryItems[] = $videoItem;
            }

            $galleryItems = $this->parentGalleryVideoProvider->sortGalleryItemsByPosition($galleryItems);

            foreach ($galleryItems as $galleryItem) {
                $images->addItem($galleryItem);
            }
        } catch (Exception $e) {
            $this->logger->error(
                'AddImagesToGalleryBlock failed to build gallery images',
                ['exception' => $e->getMessage(), 'product_id' => $subject->getProduct()?->getId()]
            );
        }

        return $images;
    }
}
