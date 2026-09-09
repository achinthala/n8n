<?php
declare(strict_types=1);

namespace Dcw\ProductImage\Block\ConfigurableProduct\Product\View\Type;

use Dcw\ProductImage\Helper\Data;
use Dcw\ProductImage\Model\ParentGalleryVideoProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ProductConfigurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class Configurable
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly LoggerInterface $logger,
        private readonly Data $dcwImageHelper,
        private readonly Image $imageHelper,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ParentGalleryVideoProvider $parentGalleryVideoProvider
    ) {
    }

    public function afterGetJsonConfig(ProductConfigurable $subject, string $result): string
    {
        try {
            $resultUnserialized = $this->serializer->unserialize($result);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error($exception->getTraceAsString(), [$result]);

            return $result;
        }

        if (isset($resultUnserialized['images'])) {
            $resultUnserializedImages = &$resultUnserialized['images'];

            $defaultPlaceholderUrl = $this->imageHelper->getDefaultPlaceholderUrl('thumbnail');
            $storeId = (int) $subject->getProduct()->getStoreId();

            foreach ($resultUnserializedImages as $productId => $productImages) {
                $product = $this->getProductById((int) $productId, $storeId);

                if (!$product) {
                    foreach ($productImages as $productImage) {
                        $this->setImageValues(
                            $resultUnserializedImages[$productId][0],
                            $defaultPlaceholderUrl,
                            $defaultPlaceholderUrl,
                            $defaultPlaceholderUrl
                        );
                    }
                    continue;
                }

                $rebuiltImages = $this->buildImagesFromGallery(
                    $product,
                    $defaultPlaceholderUrl
                );

                if ($rebuiltImages !== []) {
                    $resultUnserializedImages[$productId] = $rebuiltImages;
                }
            }
        }

        try {
            return (string) $this->serializer->serialize($resultUnserialized);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error($exception->getTraceAsString(), [$result]);

            return $result;
        }
    }

    /**
     * Build configurable option images in Magento Admin gallery order.
     */
    private function buildImagesFromGallery(
        ProductInterface $product,
        string $defaultPlaceholderUrl
    ): array {
        $thumbnailImageDimension = $this->dcwImageHelper->getThumbnailImageDimension();
        $baseImageDimension = $this->dcwImageHelper->getBaseImageDimension();
        $caption = $product->getName() ?? '';

        $images = [];
        $displayPosition = 0;

        foreach ($this->dcwImageHelper->getSortedGalleryEntries($product) as $entry) {
            if ($entry['media_type'] !== 'image') {
                continue;
            }

            if ($entry['url'] === '' || $entry['url'] === 'required') {
                continue;
            }

            $displayPosition++;
            $smallImage = $entry['url'] . '/-B' . $thumbnailImageDimension . '-FWEBP';
            $mediumImage = $entry['url'] . '/-B' . $baseImageDimension . '-FWEBP';
            $largeImage = $entry['url'] . '/-B' . $baseImageDimension . '-FWEBP';

            $images[] = [
                'thumb' => $smallImage,
                'img' => $mediumImage,
                'full' => $largeImage,
                'caption' => $caption,
                'position' => $entry['position'],
                'isMain' => $displayPosition === 1,
                'type' => 'image',
                'videoUrl' => null,
            ];
        }

        foreach ($this->parentGalleryVideoProvider->getVideoJsonEntries($product) as $childVideoEntry) {
            $images[] = $childVideoEntry;
        }

        $images = $this->parentGalleryVideoProvider->sortJsonEntriesByPosition($images);

        if ($images === []) {
            $images[] = [
                'thumb' => $defaultPlaceholderUrl,
                'img' => $defaultPlaceholderUrl,
                'full' => $defaultPlaceholderUrl,
                'caption' => $caption,
                'position' => 0,
                'isMain' => true,
                'type' => 'image',
                'videoUrl' => null,
            ];
        }

        return $images;
    }

    private function getProductById(int $productId, int $storeId): ?ProductInterface
    {
        try {
            return $this->productRepository->getById($productId, false, $storeId, true);
        } catch (NoSuchEntityException $exception) {
            $this->logger->error($exception->getTraceAsString(), [$productId]);

            return null;
        }
    }

    private function setImageValues(
        array &$image,
        string $thumb,
        string $img,
        string $full
    ): void {
        $image['thumb'] = $thumb;
        $image['img'] = $img;
        $image['full'] = $full;
    }
}
