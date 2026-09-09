<?php
declare(strict_types=1);

namespace Dcw\ProductImage\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public function getMainImageUrl($product)
    {
        foreach ($this->getSortedGalleryEntries($product) as $entry) {
            if ($entry['url'] !== '' && $entry['url'] !== 'required') {
                return $entry['url'];
            }
        }

        return '';
    }

    public function getGalleryImage($product)
    {
        $galleryImages = [];

        foreach ($this->getSortedGalleryEntries($product) as $entry) {
            if ($entry['url'] !== '' && $entry['url'] !== 'required') {
                $galleryImages[] = $entry['url'];
            }
        }

        return $galleryImages;
    }

    /**
     * Media gallery rows sorted by Magento Admin position.
     *
     * @return array<int, array{position: int, url: string, media_type: string, value_id: int}>
     */
    public function getSortedGalleryEntries($product): array
    {
        $images = $product->getMediaGalleryImages();
        if (!$images) {
            return [];
        }

        $entries = [];

        foreach ($images as $img) {
            $position = (int) ($img->getData('position') ?? $img->getPosition() ?? $img->getPositionDefault() ?? 0);
            $entries[] = [
                'position' => $position,
                'url' => (string) ($img->getData('custom_image_link') ?? $img['custom_image_link'] ?? ''),
                'media_type' => (string) ($img->getMediaType() ?? $img->getData('media_type') ?? 'image'),
                'value_id' => (int) ($img->getValueId() ?? $img->getData('value_id') ?? 0),
            ];
        }

        usort(
            $entries,
            static function (array $a, array $b): int {
                if ($a['position'] !== $b['position']) {
                    return $a['position'] <=> $b['position'];
                }

                return $a['value_id'] <=> $b['value_id'];
            }
        );

        return $entries;
    }

    /**
     * Second gallery image URL for category/list hover (Canto), or null if unavailable.
     */
    public function getListHoverImageUrl($product): ?string
    {
        $galleryImages = array_values(array_filter(
            $this->getGalleryImage($product),
            static fn (string $url): bool => $url !== '' && $url !== 'required'
        ));

        if (count($galleryImages) < 2) {
            return null;
        }

        $dimension = $this->getCategoryListImageDimension();

        return $galleryImages[1] . '/-B' . $dimension . '-FWEBP';
    }

    /**
     * Canto dimension for category/list product images (primary + hover).
     */
    public function getCategoryListImageDimension(): string
    {
        $dimension = trim((string) $this->getBaseImageDimension());

        return $dimension !== '' ? $dimension : '318';
    }

    /**
     * Normalize a Canto URL to the category list dimension suffix.
     */
    public function applyCategoryListImageDimension(string $url): string
    {
        if ($url === '') {
            return $url;
        }

        $dimension = $this->getCategoryListImageDimension();
        $suffix = '/-B' . $dimension . '-FWEBP';

        if (preg_match('#/-B\d+-FWEBP#i', $url)) {
            return (string) preg_replace('#/-B\d+-FWEBP#i', $suffix, $url);
        }

        if (stripos($url, 'FWEBP') !== false) {
            return $url;
        }

        return rtrim($url, '/') . $suffix;
    }
	
	public function getGalleryImageForChild($product)
    {
        $galleryImages = [];

        foreach ($this->getSortedGalleryEntries($product) as $entry) {
            if ($entry['media_type'] !== 'image') {
                continue;
            }

            $galleryImages[] = $entry['url'] !== '' && $entry['url'] !== 'required'
                ? $entry['url']
                : '';
        }

        return $galleryImages;
    }

    public function getConfigValue($configPath)
    {
        return $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE);
    }

    public function getMainImageDimension()
    {
        return $this->getConfigValue('dcw_productimage/product_image_config/default_product_image_original');
    }

    public function getBaseImageDimension()
    {
        return $this->getConfigValue('dcw_productimage/product_image_config/default_product_image_base');
    }

    public function getSmallImageDimension()
    {
        return $this->getConfigValue('dcw_productimage/product_image_config/default_product_image_small');
    }

    public function getThumbnailImageDimension()
    {
        return $this->getConfigValue('dcw_productimage/product_image_config/default_product_image_thumbnail');
    }
}
