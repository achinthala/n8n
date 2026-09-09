<?php

declare(strict_types=1);

namespace Dcw\Feed\Model\Export\RowCustomizer;

use Amasty\Feed\Model\Export\Product;

class Gallery extends \Amasty\Feed\Model\Export\RowCustomizer\Gallery
{
    /**
     * Override to prefer per-image custom_image_link (Canto URL) when present.
     *
     * This keeps the same structure as the original Amasty Gallery row customizer:
     * it fills amasty_custom_data[gallery] with image_1 ... image_5, but the values
     * now come from the custom_image_link field if available.
     *
     * @inheritdoc
     */
    public function addData($dataRow, $productId)
    {
        // Keep both entity ID and (when needed) row ID
        $entityId = (int)$productId;
        $productId = $this->convertEntityIdToRowIdIfNeed($entityId);
        $customData = &$dataRow['amasty_custom_data'];
        $galleryAll = $this->getGallery();
        $gallery = $galleryAll[$productId] ?? [];

        // If there is no gallery for this (simple) product, try to fall back to its configurable parent.
        if (empty($gallery)) {
            /** @var Product $exportModel */
            $exportModel = $this->export; // injected by Composite via constructor arguments
            $parentId = $exportModel->getParentIdByChildId($entityId);

            if ($parentId) {
                $parentRowId = $this->convertEntityIdToRowIdIfNeed($parentId);

                // If parent gallery was not preloaded, load it on demand and cache it locally.
                if (!isset($galleryAll[$parentRowId])) {
                    $parentGallery = $exportModel->getMediaGallery([$parentId]);
                    if (!empty($parentGallery)) {
                        // merge but do not overwrite existing keys
                        $this->gallery = $this->gallery + $parentGallery;
                        $galleryAll = $this->getGallery();
                    }
                }

                $gallery = $galleryAll[$parentRowId] ?? [];
            }
        }
        $galleryImg = [];

        foreach ($gallery as $data) {
            // Prefer external/custom Canto link; if missing or placeholder, skip (leave null in CSV).
            $rawCustomLink = $data['custom_image_link'] ?? '';
            $rawTrimmed = is_string($rawCustomLink) ? trim($rawCustomLink) : '';

            // Treat empty or "required" as invalid: do not output anything for this image.
            if ($rawTrimmed === '' || strcasecmp($rawTrimmed, 'required') === 0) {
                continue;
            }

            // Only now append the Canto rendition suffix.
            $customLink = $rawTrimmed . '/-B1824-FJPG';
            $url = $customLink;

            if (!isset($customData['image']) || !in_array($url, $customData['image'], true)) {
                $galleryImg[] = $url;
            }
        }

        $customData[Product::PREFIX_GALLERY_ATTRIBUTE] = [
            'image_1' => $galleryImg[0] ?? null,
            'image_2' => $galleryImg[1] ?? null,
            'image_3' => $galleryImg[2] ?? null,
            'image_4' => $galleryImg[3] ?? null,
            'image_5' => $galleryImg[4] ?? null,
        ];

        return $dataRow;
    }
}


