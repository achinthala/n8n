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
 * @version   2.2.49
 * @copyright Copyright (C) 2024 Mirasvit (https://mirasvit.com/)
 */

declare(strict_types=1);

namespace Dcw\AdvanceSearch\Index\Magento\Catalog\Product\InstantProvider;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\View\LayoutInterface;
use Mirasvit\Search\Model\ConfigProvider;
use Mirasvit\Search\Service\MapperService;
use Magento\Tax\Helper\Data as TaxHelper;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\Pricing\Render;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Dcw\AdvanceSearch\ViewModel\Data as AdvanceSearchViewModel;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomMapper extends \Mirasvit\Search\Index\Magento\Catalog\Product\InstantProvider\Mapper
{
    const IN_STOCK = 2;

    const OUT_OF_STOCK = 1;

    const UNSET_STOCK = 0;

    private $attributeIds = [];

    private $pkField      = '';

    private $resource;

    private $state;

    private $configProvider;

    private $mapperService;

    private $imageHelper;

    private $pricingHelper;

    private $taxHelper;

    private $catalogHelper;

    private $layout;

    private $priceRender;

    private $productCollectionFactory;

    protected $advanceSearchViewModel;

    public function __construct(
        ResourceConnection       $resource,
        State                    $state,
        ConfigProvider           $configProvider,
        MapperService            $mapperService,
        ImageHelper              $imageHelper,
        PricingHelper            $pricingHelper,
        TaxHelper                $taxHelper,
        CatalogHelper            $catalogHelper,
        LayoutInterface          $layout,
        ProductCollectionFactory $productCollectionFactory,
        AdvanceSearchViewModel $advanceSearchViewModel
    ) {
        $this->resource                 = $resource;
        $this->state                    = $state;
        $this->configProvider           = $configProvider;
        $this->mapperService            = $mapperService;
        $this->imageHelper              = $imageHelper;
        $this->pricingHelper            = $pricingHelper;
        $this->taxHelper                = $taxHelper;
        $this->catalogHelper            = $catalogHelper;
        $this->layout                   = $layout;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->advanceSearchViewModel = $advanceSearchViewModel;

        parent::__construct(
            $resource,
            $state,
            $configProvider,
            $mapperService,
            $imageHelper,
            $pricingHelper,
            $taxHelper,
            $catalogHelper,
            $layout,
            $productCollectionFactory
        );
    }

    public function mapProductPrice(int $storeId, array $productIds): array
    {
        return $this->mapProductPriceNew($storeId, $productIds);
    }

    public function mapProductUrl(int $storeId, array $productIds): array
    {
        $data = $this->resource->getConnection()->fetchPairs(
            $this->resource->getConnection()
                ->select()
                ->from([$this->resource->getTableName('url_rewrite')], ['entity_id', 'request_path'])
                ->where('entity_id IN(?)', $productIds)
                ->where('entity_type = ? ', ProductUrlRewriteGenerator::ENTITY_TYPE)
                ->where('store_id IN(?)', [$storeId])
                ->where('redirect_type = 0')
                ->group('entity_id')
        );

        $map = [];
        foreach ($productIds as $productId) {
            $map[$productId] = $this->mapperService->getBaseUrl($storeId) . 'catalog/product/view/id/' . $productId . '/';
        }

        foreach ($data as $productId => $requestPath) {
            if ($requestPath && !str_contains($requestPath, '/view/id')) {
                $map[$productId] = $this->mapperService->getBaseUrl($storeId) . 'shop/'.$requestPath;
            } else {
                $map[$productId] = $this->mapperService->getBaseUrl($storeId) . $requestPath;
            }
        }

        return $map;
    }

    public function mapProductPriceNew(int $storeId, array $productIds): array
    {
        $finalData = $map = [];

        // Get instance of the Object Manager
        //$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        // Load a class, for example, ProductRepositoryInterface
        //$advanceSearchData = $objectManager->get(\Dcw\AdvanceSearch\ViewModel\Data::class);

        if (count($productIds) > 0) {
            if ($this->configProvider->getPriceFetchStrategy() == 'magento_short') {
                $finalData = $this->mapProductPriceMagentoShort($storeId, $productIds);
            } elseif ($this->configProvider->getPriceFetchStrategy() == 'magento_default') {
                $finalData = $this->mapProductPriceMagentoDefault($storeId, $productIds);
            } else {
                $finalData =  $this->mapProductPriceSql($storeId, $productIds);
            }
    
            foreach ($finalData as $key => $value) {
                $getCalculatedPrice = $this->advanceSearchViewModel->getCalculatedPrice($key);

                if (isset($getCalculatedPrice['calType']) && isset($getCalculatedPrice['price'])) {

                    if ($getCalculatedPrice['calType'] != 'none') {
                        $map[$key] = $this->pricingHelper->currencyByStore($getCalculatedPrice['price'], $storeId, true, false).'/'.$getCalculatedPrice['calType'];
                    } else {
                        $map[$key] = $this->pricingHelper->currencyByStore($getCalculatedPrice['price'], $storeId, true, false);
                    }
                }
            }
        }

        return $map;
    }
}
