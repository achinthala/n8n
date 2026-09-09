<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-search-ultimate
 * @version   2.1.1
 * @copyright Copyright (C) 2023 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Dcw\AdvanceSearch\Index\Magento\Catalog\Product;

use Mirasvit\Search\Index\AbstractInstantProvider;
use Mirasvit\Search\Service\IndexService;

class InstantProvider extends \Mirasvit\Search\Index\Magento\Catalog\Product\InstantProvider
{
    private $mapper;

    public function __construct(
        InstantProvider\Mappernew $mapper
    ) {
        $this->mapper = $mapper;
    }
    
    public function getItems(int $storeId, int $limit, int $page = 1): array
    {
        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $collection */
        $collection = $this->getCollection($limit, $page)
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('short_description')
            ->addAttributeToSelect('description')
            ->setOrder('relevance');

        $entityIds = [];
        foreach ($collection as $product) {
            $entityIds[] = $product->getId();
        }

        $maps = [
            'sku'          => $this->mapper->mapProductSku($storeId, $entityIds),
            'name'         => $this->mapper->mapProductName($storeId, $entityIds),
            'description'  => $this->mapper->mapProductDescription($storeId, $entityIds),
            'url'          => $this->mapper->mapProductUrl($storeId, $entityIds),
            'imageUrl'     => $this->mapper->mapProductImage($storeId, $entityIds),
            'price'        => $this->mapper->mapProductPrice($storeId, $entityIds),
            'stockStatus'  => $this->mapper->mapProductStockStatus($storeId, $entityIds),
            'addToCartUrl' => $this->mapper->mapProductCart($storeId, $entityIds),
            'rating'       => $this->mapper->mapProductRating($storeId, $entityIds),
            'reviews'      => $this->mapper->mapProductReviews($storeId, $entityIds),
            'dealOfTheMonth' => $this->mapper->mapProductDealOfTheMonth($storeId, $entityIds),
            'freeShipping' => $this->mapper->mapProductFreeShipping($storeId, $entityIds),
            'shipNextDay' => $this->mapper->mapProductShipNextDay($storeId, $entityIds),
            'colorCount' => $this->mapper->mapProductColorCount($storeId, $entityIds),
            'orderFreeSample' => $this->mapper->mapProductOrderFreeSample($storeId, $entityIds),
            'proId' => $this->mapper->mapProductId($storeId, $entityIds),
        ];

        $result = [];
        foreach ($maps as $key => $items) {
            foreach ($items as $productId => $value) {
                $result[$productId][$key] = $value;
            }
        }

        return array_values($result);
    }

    public function getSize(int $storeId): int
    {
        return $this->getCollection(0)->getSize();
    }

    public function map(array $documentData, int $storeId): array
    {
        $entityIds = [];
        foreach (array_keys($documentData) as $productId) {
            $entityIds[] = (int)$productId;
        }

        foreach ($documentData as $productId => $value) {
            $documentData[$productId]['_instant'] = [
                'name'         => '',
                'url'          => '',
                'sku'          => '',
                'description'  => '',
                'imageUrl'     => '',
                'price'        => '',
                'rating'       => '',
                'reviews'      => '',
                'addToCartUrl' => '',
                'stockStatus'  => '',
                'dealOfTheMonth' => '',
                'freeShipping' => '',
                'shipNextDay' => '',
                'colorCount' => '',
                'orderFreeSample' => '',
                'proId' => '',
            ];
        }

        $maps = [
            'sku'          => $this->mapper->mapProductSku($storeId, $entityIds),
            'name'         => $this->mapper->mapProductName($storeId, $entityIds),
            'description'  => $this->mapper->mapProductDescription($storeId, $entityIds),
            'url'          => $this->mapper->mapProductUrl($storeId, $entityIds),
            'imageUrl'     => $this->mapper->mapProductImage($storeId, $entityIds),
            'price'        => $this->mapper->mapProductPrice($storeId, $entityIds),
            'stockStatus'  => $this->mapper->mapProductStockStatus($storeId, $entityIds),
            'addToCartUrl' => $this->mapper->mapProductCart($storeId, $entityIds),
            'rating'       => $this->mapper->mapProductRating($storeId, $entityIds),
            'reviews'      => $this->mapper->mapProductReviews($storeId, $entityIds),
            'dealOfTheMonth' => $this->mapper->mapProductDealOfTheMonth($storeId, $entityIds),
            'freeShipping' => $this->mapper->mapProductFreeShipping($storeId, $entityIds),
            'shipNextDay' => $this->mapper->mapProductShipNextDay($storeId, $entityIds),
            'colorCount' => $this->mapper->mapProductColorCount($storeId, $entityIds),
            'orderFreeSample' => $this->mapper->mapProductOrderFreeSample($storeId, $entityIds),
            'proId' => $this->mapper->mapProductId($storeId, $entityIds),
        ];

        foreach ($maps as $key => $items) {
            foreach ($items as $productId => $value) {
                $documentData[$productId]['_instant'][$key] = $value;
            }
        }

        return $documentData;
    }
}
