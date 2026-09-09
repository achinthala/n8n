<?php
declare(strict_types=1);

namespace Dcw\ProductImage\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * Product gallery video/external-media entries for PDP and configurable JSON.
 */
class ParentGalleryVideoProvider
{
  /** @var array<int, array<string, string>> */
    private array $galleryImagesConfig;

    public function __construct(
        private readonly UrlBuilder $imageUrlBuilder,
        private readonly State $appState
    ) {
        $this->galleryImagesConfig = [
            'small_image' => [
                'image_id' => 'product_page_image_small',
                'data_object_key' => 'small_image_url',
            ],
            'medium_image' => [
                'image_id' => 'product_page_image_medium',
                'data_object_key' => 'medium_image_url',
            ],
            'large_image' => [
                'image_id' => 'product_page_image_large',
                'data_object_key' => 'large_image_url',
            ],
        ];
    }

    /**
     * Gallery video/external-media items with PDP image URLs applied.
     *
     * @return DataObject[]
     */
    public function getEnrichedNonImageItems(ProductInterface $product): array
    {
        $galleryImages = $product->getMediaGalleryImages();
        if (!$galleryImages) {
            return [];
        }

        $items = [];

        foreach ($galleryImages as $image) {
            if ($this->isImageMediaType($image->getMediaType())) {
                continue;
            }

            $this->applyGalleryImageUrls($image);
            $items[] = $image;
        }

        return $items;
    }

    /**
     * Video entries for gallery JS / configurable option JSON.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getVideoJsonEntries(ProductInterface $product): array
    {
        $entries = [];

        foreach ($this->getEnrichedNonImageItems($product) as $image) {
            $mediaType = $image->getMediaType();

            $entries[] = [
                'thumb' => $image->getData('small_image_url'),
                'img' => $image->getData('medium_image_url'),
                'full' => $image->getData('large_image_url'),
                'caption' => $image->getLabel() ?: $product->getName(),
                'position' => $image->getData('position'),
                'isMain' => false,
                'type' => $mediaType !== null ? str_replace('external-', '', (string) $mediaType) : '',
                'videoUrl' => $image->getVideoUrl(),
            ];
        }

        return $entries;
    }

    /**
     * @param array<int, array<string, mixed>> $images
     * @return array<int, array<string, mixed>>
     */
    public function sortJsonEntriesByPosition(array $images): array
    {
        usort(
            $images,
            static function (array $a, array $b): int {
                $positionA = (int) ($a['position'] ?? 0);
                $positionB = (int) ($b['position'] ?? 0);

                if ($positionA !== $positionB) {
                    return $positionA <=> $positionB;
                }

                $typeA = ($a['type'] ?? '') === 'video' ? 1 : 0;
                $typeB = ($b['type'] ?? '') === 'video' ? 1 : 0;

                return $typeA <=> $typeB;
            }
        );

        return $images;
    }

    /**
     * @param DataObject[] $items
     * @return DataObject[]
     */
    public function sortGalleryItemsByPosition(array $items): array
    {
        usort(
            $items,
            static function (DataObject $a, DataObject $b): int {
                $positionA = (int) ($a->getData('position') ?? 0);
                $positionB = (int) ($b->getData('position') ?? 0);

                if ($positionA !== $positionB) {
                    return $positionA <=> $positionB;
                }

                $typeA = $a->getMediaType() === 'external-video' ? 1 : 0;
                $typeB = $b->getMediaType() === 'external-video' ? 1 : 0;

                return $typeA <=> $typeB;
            }
        );

        return $items;
    }

    private function applyGalleryImageUrls(DataObject $image): void
    {
        $file = (string) $image->getFile();
        if ($file !== '' && $file !== 'no_selection') {
            foreach ($this->galleryImagesConfig as $imageConfig) {
                $image->setData(
                    $imageConfig['data_object_key'],
                    $this->imageUrlBuilder->getUrl($file, $imageConfig['image_id'])
                );
            }
        }

        if ($this->shouldUseExternalVideoThumbnail($file, $image)) {
            $this->applyExternalVideoThumbnailFallback($image);
        }
    }

    private function shouldUseExternalVideoThumbnail(string $file, DataObject $image): bool
    {
        $smallUrl = (string) $image->getData('small_image_url');
        if ($smallUrl === '') {
            return true;
        }

        if ($file === '' || $file === 'no_selection') {
            return false;
        }

        if ($this->isLocalPreviewFileAvailable($file)) {
            return false;
        }

        try {
            return $this->appState->getMode() === State::MODE_DEVELOPER;
        } catch (LocalizedException) {
            return false;
        }
    }

    private function applyExternalVideoThumbnailFallback(DataObject $image): void
    {
        $externalThumb = $this->getExternalVideoThumbnailUrl((string) $image->getVideoUrl());
        if ($externalThumb === null) {
            return;
        }

        foreach (['small_image_url', 'medium_image_url', 'large_image_url'] as $imageKey) {
            $image->setData($imageKey, $externalThumb);
        }
    }

    private function isLocalPreviewFileAvailable(string $file): bool
    {
        return is_file(BP . '/pub/media/catalog/product' . $file);
    }

    private function getExternalVideoThumbnailUrl(string $videoUrl): ?string
    {
        if ($videoUrl === '') {
            return null;
        }

        if (preg_match('/youtube\.com|youtu\.be|youtube-nocookie\.com/', $videoUrl)) {
            $youtubeRegex = '/^.*(?:youtu\.be\/|v\/|vi\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/';
            if (preg_match($youtubeRegex, $videoUrl, $matches)) {
                $id = $matches[1] ?? '';
                if ($id !== '') {
                    return 'https://img.youtube.com/vi/' . $id . '/hqdefault.jpg';
                }
            }
        }

        if (preg_match('/vimeo\.com/', $videoUrl)) {
            $vimeoRegex = '/(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d+)/';
            if (preg_match($vimeoRegex, $videoUrl, $matches)) {
                return 'https://vumbnail.com/' . $matches[1] . '.jpg';
            }
        }

        return null;
    }

    private function isImageMediaType(?string $mediaType): bool
    {
        return $mediaType === null || $mediaType === '' || $mediaType === 'image';
    }
}
