<?php

namespace WeltPixel\GA4\Helper;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MetaPixelTracking extends Data
{
    /**
     * @return boolean
     */
    public function isMetaPixelTrackingEnabled() {
        return $this->_gtmOptions['meta_pixel_tracking']['enable'];
    }

    /**
     * @return string
     */
    public function getMetaPixelCodeSnippet() {
        return trim($this->_gtmOptions['meta_pixel_tracking']['code_snippet'] ?? '');
    }


    /**
     * @return array
     */
    public function getMetaPixelTrackedEvents() {
        $trackedEvents = $this->_gtmOptions['meta_pixel_tracking']['events'] ?? '';
        return explode(',', $trackedEvents);
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldMetaPixelEventBeTracked($eventName) {
        $availableEvents = $this->getMetaPixelTrackedEvents();
        return in_array($eventName, $availableEvents);
    }

    /**
     * @param array $categoryIds
     * @return string
     */
    public function getContentCategory($categoryIds)
    {
        $categoriesArray = $this->getGA4CategoriesFromCategoryIds($categoryIds);
        return implode(", ", $categoriesArray);
    }


    /**
     * @param $product
     * @param int $qty
     * @return array
     */
    public function metaPixelAddToCartPushData($product, $qty = 1)
    {
        $result = [
            'track' => 'track',
            'eventName' => 'AddToCart',
            'eventData' => []
        ];

        $productId = $this->getMetaProductId($product);
        $productCategoryIds = $product->getCategoryIds();

        $result['eventData']['content_type'] = 'product';
        $result['eventData']['quantity'] = $qty;
        $result['eventData']['currency'] = $this->getCurrencyCode();
        $result['eventData']['content_ids'] = [$productId];
        $result['eventData']['content_name'] = html_entity_decode($product->getName() ?? '');
        $result['eventData']['content_category'] = addslashes(str_replace('"','&quot;',$this->getContentCategory($productCategoryIds)));
        $result['eventData']['value'] = floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', ''));

        return $result;
    }

    /**
     * @param $product
     * @return array
     */
    public function metaPixelAddToWishlistPushData($product)
    {
        $result = [
            'track' => 'track',
            'eventName' => 'AddToWishlist',
            'eventData' => []
        ];

        $productId = $this->getMetaProductId($product);
        $productCategoryIds = $product->getCategoryIds();

        $result['eventData']['content_type'] = 'product';
        $result['eventData']['currency'] = $this->getCurrencyCode();
        $result['eventData']['content_ids'] = [$productId];
        $result['eventData']['content_name'] = html_entity_decode($product->getName() ?? '');
        $result['eventData']['content_category'] = addslashes(str_replace('"','&quot;',$this->getContentCategory($productCategoryIds)));
        $result['eventData']['value'] = floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', ''));

        return $result;
    }

    /**
     * Returns the product id or sku based on the backend settings
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getMetaProductId($product)
    {
        $idOption = $this->_gtmOptions['meta_pixel_tracking']['id_selection'];
        $metaProductId = '';

        switch ($idOption) {
            case 'sku':
                $metaProductId = $product->getData('sku');
                break;
            case 'id':
            default:
                $metaProductId = $product->getId();
                break;
        }

        return $metaProductId;
    }
}
