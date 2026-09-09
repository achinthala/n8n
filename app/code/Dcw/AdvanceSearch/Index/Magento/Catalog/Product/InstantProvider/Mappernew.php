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



namespace Dcw\AdvanceSearch\Index\Magento\Catalog\Product\InstantProvider;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Mirasvit\Search\Service\MapperService;
use Magento\Catalog\Model\ProductFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Mappernew
{

    public const IN_STOCK = 2;

    public const OUT_OF_STOCK = 1;

    public const UNSET_STOCK = 0;

    private $attributeIds = [];

    private $pkField      = '';

    private $resource;

    private $mapperService;

    private $imageHelper;

    private $pricingHelper;

    protected $layoutFactory;

    protected $productFactory;

	protected $dcwimagehelper;

    public function __construct(
        ResourceConnection $resource,
        MapperService      $mapperService,
        ImageHelper        $imageHelper,
        PricingHelper      $pricingHelper,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        ProductFactory $productFactory,
		\Dcw\ProductImage\Helper\Data $dcwimagehelper,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->resource      = $resource;
        $this->mapperService = $mapperService;
        $this->imageHelper   = $imageHelper;
        $this->pricingHelper = $pricingHelper;
        $this->layoutFactory = $layoutFactory;
        $this->productFactory = $productFactory;
		$this->dcwimagehelper = $dcwimagehelper;
        $this->eavConfig = $eavConfig;
    }

    public function mapProductSku(int $storeId, array $productIds): array
    {
        $data = $this->resource->getConnection()->fetchPairs(
            $this->resource->getConnection()
                ->select()
                ->from([$this->resource->getTableName('catalog_product_entity')], ['entity_id', 'sku'])
                ->where('entity_id IN(?)', $productIds)
        );

        $map = [];
        foreach ($productIds as $productId) {
            $map[$productId] = '';
        }

        foreach ($data as $productId => $sku) {
            $map[$productId] = $this->mapperService->clearString((int)$storeId, (string)$sku, false);
        }

        return $map;
    }

    public function mapProductName(int $storeId, array $productIds): array
    {
        $data = $this->attributeSelectQuery($storeId, $productIds, 'name', 'varchar');

        $map = [];
        foreach ($productIds as $productId) {
            $map[$productId] = '';
        }

        foreach ($data as $productId => $name) {
            $map[$productId] = $this->mapperService->clearString((int)$storeId, (string)$name, false);
        }

        return $map;
    }

    public function mapProductOrderFreeSample(int $storeId, array $productIds): array
    {
        $sampleblock= $this->layoutFactory->create()->createBlock(\Dcw\SampleProduct\Block\SampleData::class);
        foreach ($productIds as $productId) {
            $map[$productId] = ($sampleblock->getsampleproductsku($productId))?"1":"0";
        }
        return $map;
    }

    public function mapProductId(int $storeId, array $productIds): array
    {
        foreach ($productIds as $productId) {
            $map[$productId] = $productId;
        }
        return $map;
    }

    public function mapProductDealOfTheMonth(int $storeId, array $productIds): array
    {
        $data = $this->attributeSelectQuery($storeId, $productIds, 'incstores_pim_home_page_feature_flag', 'int');

        $map = [];
        foreach ($productIds as $productId) {
            $map[$productId] = 0;
        }

        foreach ($data as $productId => $val) {
            $map[$productId] = ($val == $this->eavConfig->getAttribute ('catalog_product', 'incstores_pim_home_page_feature_flag')->getSource ()->getOptionId ('Deal of the Month')) ? "Deal of the Month" : "";
        }

        return $map;
    }

    public function mapProductFreeShipping(int $storeId, array $productIds): array
    {
        $data = $this->attributeSelectQuery($storeId, $productIds, 'incstores_pim_home_page_feature_flag', 'int');

        $map = [];
        foreach ($productIds as $productId) {
            $map[$productId] = 0;
        }

        foreach ($data as $productId => $val) {
            $map[$productId] = ($val == $this->eavConfig->getAttribute ('catalog_product', 'incstores_pim_home_page_feature_flag')->getSource ()->getOptionId ('Free Shipping')) ? "Free Shipping" : "";
        }

        return $map;
    }

    public function mapProductShipNextDay(int $storeId, array $productIds): array
    {
        $data = $this->attributeSelectQuery($storeId, $productIds, 'quick_ship', 'int');

        $map = [];
        foreach ($productIds as $productId) {
            $map[$productId] = 0;
        }

        foreach ($data as $productId => $val) {
            $map[$productId] = ($val == $this->eavConfig->getAttribute ('catalog_product', 'quick_ship')->getSource ()->getOptionId ('Quick Ship')) ? "Ships Next Business Day" : "";
        }

        return $map;
    }

    public function mapProductUrl(int $storeId, array $productIds): array
    {
        $data = $this->resource->getConnection()->fetchPairs(
            $this->resource->getConnection()
                ->select()
                ->from([$this->resource->getTableName('url_rewrite')], ['entity_id', 'request_path'])
                ->where('entity_id IN(?)', $productIds)
                ->where('entity_type = ? ', ProductUrlRewriteGenerator::ENTITY_TYPE)
                ->where('store_id IN(?)', [0, $storeId])
                ->where('redirect_type = 0')
                ->group('entity_id')
        );

        $map = [];
        foreach ($productIds as $productId) {
            $map[$productId] = $this->mapperService
                                    ->getBaseUrl($storeId) . 'catalog/product/view/id/' . $productId . '/';
        }

        foreach ($data as $productId => $requestPath) {
            $map[$productId] = $this->mapperService->getBaseUrl($storeId) . $requestPath;
        }

        return $map;
    }

    public function mapProductImage(int $storeId, array $productIds): array
    {
        $emulation = ObjectManager::getInstance()->get(\Magento\Store\Model\App\Emulation::class);
        $emulation->startEnvironmentEmulation($storeId, 'frontend', true);

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $data = $this->attributeSelectQuery($storeId, $productIds, 'thumbnail', 'varchar');

        $placeholder = $this->imageHelper->getDefaultPlaceholderUrl('thumbnail');

        $map = [];
		$imagesdimensionmedium = $this->dcwimagehelper->getSmallImageDimension();
        foreach ($productIds as $productId) {
			$productnew = $objectManager->create('Magento\Catalog\Model\Product')->load($productId);
			$imageexternal = $this->dcwimagehelper->getMainImageUrl($productnew);
			if($imageexternal!=""){
				$proimg = $imageexternal.'/-B'.$imagesdimensionmedium;

			} else {
				$proimg = $placeholder;
			}
            $map[$productId] = $proimg;
        }

        foreach ($data as $productId => $path) {
            if ($path == '' || $path == 'no_selection') {
                continue;
            }

            $image = (string)$this->imageHelper->init(null, 'upsell_products_list')
                ->setImageFile($path)
                ->getUrl();

            if ($image == '') {
                $image = $placeholder;
            }

			$product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId);
			$imagenew = $this->dcwimagehelper->getMainImageUrl($product);

			if($imagenew!=""){
				$imageone = $imagenew.'/-B'.$imagesdimensionmedium;
			} else {
				$imageone = $placeholder;
			}

            $map[$productId] = $imageone;
        }

        $emulation->stopEnvironmentEmulation();

        return $map;
    }

    public function mapProductPrice(int $storeId, array $productIds): array
    {
        $data = $this->resource->getConnection()->fetchAll(
            $this->resource->getConnection()
                ->select()
                ->from(['cpip' => $this->resource->getTableName('catalog_product_index_price')], ['*'])
                ->join(['s' => $this->resource->getTableName('store')], 's.website_id = cpip.website_id', [])
                ->where('entity_id IN(?)', $productIds)
                ->where('s.store_id = ?', $storeId)
                ->group('entity_id')
        );

        $pids = implode(",",$productIds);
        $connection = $this->resource->getConnection();
        $query = "SELECT min(cp.price) AS min_price, min(cp.final_price) AS min_final_price, cp.entity_id, cpr.parent_id FROM catalog_product_index_price AS cp JOIN catalog_product_relation AS cpr ON cp.entity_id = cpr.child_id WHERE cpr.parent_id IN ($pids) GROUP BY cpr.parent_id;";
        $result = $connection->fetchAll($query);

        $map = [];
        foreach ($productIds as $productId) {
            $map[$productId] = '';
        }

        foreach ($data as $item) {
            $productId = $item['entity_id'];
            $_product = $this->productFactory->create()->load($productId);
            $exactWidth = $_product->getData('incstores_pim_exact_width_inches');
            $exactLength = $_product->getData('incstores_pim_exact_length_inches');
            $coverage = $_product->getIncstoresPimCoverage();
            $exactHeight = $_product->getData('incstores_pim_exact_height_inches');
            $floorCalculatorType = $_product->getAttributeText('incstores_pim_calculator_type');
            if(isset($floorCalculatorType) && ($floorCalculatorType == 'case' || $floorCalculatorType == 'roll' ||$floorCalculatorType == 'sqft'))
            {
            $calculatorType = 'sqft';
            }
            else
            {
            $calculatorType = 'each';
            }
            $price = 0;
            $fprice = 0;

            if ($item['max_price'] != 0) {
                $price = $item['max_price'];
            }
            if ($item['min_price'] != 0) {
                $price = $item['min_price'];
            }
            if ($item['final_price'] != 0) {
                $fprice = $item['final_price'];
            }
            if ($item['price'] != 0) {
                $price = $item['price'];
            }

            if ($price <= 0) {
                continue;
            }
    if(($calculatorType != '' && $coverage > 0) || ($calculatorType != '' && $exactWidth > 0 && $exactLength > 0))
    {
        if($floorCalculatorType == 'case')
        {
            $minPriceSquarfeet = $coverage;
        }
        else
        {
        if (is_numeric($exactWidth) && is_numeric($exactLength)) {
            $exactWidth = $exactWidth / 12;
            $exactLength = $exactLength / 12;
            $minPriceSquarfeet = $exactWidth * $exactLength;
        }  else {
            $minPriceSquarfeet = 0;
        }
        }
        if($minPriceSquarfeet > 0 && $price>0 )
        {
            if($price > $fprice && $fprice > 0)
            {
                $displayPrice = $fprice/$minPriceSquarfeet;
                $displayMrp = $price/$minPriceSquarfeet;
            }
            else
            {
                $displayPrice = $price/$minPriceSquarfeet;
            }
        }
    } else {
        $displayPrice = $fprice;
        $displayMrp = $price;
    }

            if ($fprice == $price) {
                $map[$productId] = $this->pricingHelper->currencyByStore($displayPrice, $storeId, true, false)." /".$calculatorType;
            } elseif ($fprice == 0) {
                $map[$productId] = $this->pricingHelper->currencyByStore($price, $storeId, true, false)." /".$calculatorType;
            } else {
                $map[$productId] = $this->pricingHelper
                ->currencyByStore($displayPrice, $storeId, true, false)." /".$calculatorType."<span>".$this->pricingHelper
                ->currencyByStore($displayMrp, $storeId, true, false)." /".$calculatorType."</span>";
            }
        }


        if($result){
            foreach ($result as $pdata) {
                $pid = $pdata['parent_id'];
                $product = $this->productFactory->create()->load($pid);
                $width = $product->getData('incstores_pim_exact_width_inches');
                $length = $product->getData('incstores_pim_exact_length_inches');
                $coverageArea = $product->getIncstoresPimCoverage();
                $floorCalculator = $product->getAttributeText('incstores_pim_calculator_type');
                if (!is_numeric($width) && !is_numeric($length) || !is_numeric($coverageArea)) {

                $minPriceChildProductId = 0;
                if(!is_null($width))
                {
                if (strpos($width, '_') !== false)
                {
                $configExactWidthArr = explode('_', $width);
                $width = $configExactWidthArr[1];
                if($configExactWidthArr[0] > 0)
                {
                $minPriceChildProductId = $configExactWidthArr[0];
                }
                }
                }
                if(!is_null($length))
                {
                if (strpos($length, '_') !== false)
                {
                $configExactLengthArr = explode('_', $length);
                $length = $configExactLengthArr[1];
                if($configExactLengthArr[0] > 0)
                {
                $minPriceChildProductId = $configExactLengthArr[0];
                }
                }
                }

                if($minPriceChildProductId > 0)
                {
                $minPriceChildProduct  = $this->productFactory->create()->load($minPriceChildProductId);;
                $floorCalculator = $minPriceChildProduct->getAttributeText('incstores_pim_calculator_type');
                }
                else
                {
                $floorCalculator = $product->getAttributeText('incstores_pim_calculator_type');
                }
                 }
                if(isset($floorCalculator) && ($floorCalculator == 'case' || $floorCalculator == 'roll' ||$floorCalculator == 'sqft'))
                {
                $calType = 'sqft';
                }
                else
                {
                $calType = 'each';
                }
                $fprice= $pdata['min_final_price'];
                $price= $pdata['min_price'];
                if(($calType != '' && $coverageArea > 0) || ($calType != '' && $width > 0 && $length > 0))
                {
                if($floorCalculator == 'case')
                {
                $minSquarfeet = $coverageArea;
                }
                else
                {
                if (is_numeric($width) && is_numeric($length)) {
                $width = $width / 12;
                $length = $length / 12;
                $minSquarfeet = $width * $length;
                }  else {
                $minSquarfeet = 0;
                }
                }
                if($minSquarfeet > 0 && $price>0 )
                {
                if($price > $fprice && $fprice > 0)
                {
                $displayPrice = $fprice/$minSquarfeet;
                $displayMrp = $price/$minSquarfeet;
                }
                else
                {
                $displayPrice = $price/$minSquarfeet;
                }
                }
                } else {
                $displayPrice = $fprice;
                $displayMrp = $price;
                }
            if ($fprice == $price) {
                $map[$pid] = $this->pricingHelper->currencyByStore($displayPrice, $storeId, true, false)." /".$calType;
            } elseif ($fprice == 0) {
                $map[$pid] = $this->pricingHelper->currencyByStore($price, $storeId, true, false)." /".$calType;
            } else {
                $map[$pid] = $this->pricingHelper
                ->currencyByStore($displayPrice, $storeId, true, false)." /".$calType."  <span>".$this->pricingHelper
                ->currencyByStore($displayMrp, $storeId, true, false)." /".$calType." </span>";
            }

            }
        }

        return $map;
    }

    public function mapProductDescription(int $storeId, array $productIds): array
    {
        $descriptionData = $this->attributeSelectQuery($storeId, $productIds, 'description', 'text');

        $shortDescriptionData = $this->attributeSelectQuery($storeId, $productIds, 'short_description', 'text');

        $map = [];
        foreach ($productIds as $productId) {
            $map[$productId] = '';

            if (isset($descriptionData[$productId]) && $descriptionData[$productId] != '') {
                $map[$productId] = $this->mapperService->clearString(
                    (int)$storeId,
                    (string)$descriptionData[$productId],
                    true
                );
            } elseif (isset($shortDescriptionData[$productId]) && $shortDescriptionData[$productId] != '') {
                $map[$productId] = $this->mapperService->clearString(
                    (int)$storeId,
                    (string)$shortDescriptionData[$productId],
                    true
                );
            }
        }

        return $map;
    }

    public function mapProductRating(int $storeId, array $productIds): array
    {
        $data = $this->resource->getConnection()->fetchPairs(
            $this->resource->getConnection()->select()->from(
                [$this->resource->getTableName('rating_option_vote_aggregated')],
                [
                    'entity_pk_value',
                    'value' => new \Zend_Db_Expr('AVG(percent)'),
                ]
            )->where('entity_pk_value IN (?)', $productIds)
                ->where('store_id = ?', $storeId)
                ->group('entity_pk_value')
        );

        $map = [];
        foreach ($productIds as $productId) {
            $map[$productId] = 0;
        }

        foreach ($data as $productId => $rating) {
            $map[$productId] = $rating;
        }

        return $map;
    }

    public function mapProductReviews(int $storeId, array $productIds): array
    {
        $data = $this->resource->getConnection()->fetchPairs(
            $this->resource->getConnection()->select()->from(
                [$this->resource->getTableName('review')],
                [
                    'entity_pk_value',
                    'value' => new \Zend_Db_Expr('COUNT(review_id)'),
                ]
            )->where('status_id=1')
                ->where('entity_pk_value IN (?)', $productIds)
                ->group('entity_pk_value')
        );

        $map = [];
        foreach ($productIds as $productId) {
            $map[$productId] = 0;
        }

        foreach ($data as $productId => $reviews) {
            $map[$productId] = $reviews;
        }

        return $map;
    }

    public function mapProductColorCount(int $storeId, array $productIds): array
    {
        $pids = implode(",",$productIds);
        $map = [];
        $connection = $this->resource->getConnection();
        $query = "SELECT cpr.parent_id, COUNT(DISTINCT cpei.value) AS ccount
        FROM catalog_product_relation AS cpr
        JOIN catalog_product_entity_int AS cpei ON cpei.row_id = cpr.child_id
        JOIN eav_attribute AS ea ON ea.attribute_id = cpei.attribute_id
        WHERE cpr.parent_id IN (".$pids.")
        AND ea.attribute_code = 'color'
        GROUP BY cpr.parent_id;";
        // Bind the product ID parameter
        $bind = [':product_ids' => $productIds];
        // Execute the query with parameters
        $result = $connection->fetchAll($query);
        foreach ($productIds as $productId) {
            $map[$productId] = 0;
        }
        if($result){
            foreach ($result as $colorcount) {
                $pid = $colorcount['parent_id'];
                $map[$pid] = $colorcount['ccount'];
            }
        }
        return $map;
    }

    public function mapProductCart(int $storeId, array $productIds): array
    {
        $map = [];
        foreach ($productIds as $productId) {
            $map[$productId] = $this->mapperService->
            getBaseUrl($storeId) . 'searchautocomplete/cart/add/id/' . $productId;
        }

        $stockMap = $this->mapProductStockStatus($storeId, $productIds);

        foreach ($stockMap as $productId => $stockStatus) {
            if ($stockStatus == self::OUT_OF_STOCK) {
                $map[$productId] = '';
            }
        }

        return $map;
    }

    public function mapProductStockStatus(int $storeId, array $productIds): array
    {
        $map = [];
        foreach ($productIds as $productId) {
            $map[$productId] = self::UNSET_STOCK;
        }

        $data = $this->resource->getConnection()->fetchPairs(
            $this->resource->getConnection()->select()
                ->from(
                    ['e' => $this->resource->getTableName('catalog_product_entity')],
                    ['entity_id']
                )->joinInner(
                    ['stock' => $this->resource->getTableName('cataloginventory_stock_status')],
                    'stock.product_id = e.entity_id',
                    ['stock_status']
                )
                ->where('e.entity_id IN (?)', $productIds)
                ->group('e.entity_id')
        );

        foreach ($data as $productId => $stockStatus) {
            $map[$productId] = $stockStatus == 1 ? self::IN_STOCK : self::OUT_OF_STOCK;
        }

        return $map;
    }

    private function attributeSelectQuery(int $storeId, array $productIds, string $attribute, string $type): array
    {
        $map = [];
        foreach ($productIds as $productId) {
            $map[$productId] = '';
        }

        $mainTable = 'catalog_product_entity_' . $type;

        foreach ([$storeId, 0, 1] as $sid) {
            $query = $this->resource->getConnection()
                ->select()
                ->from(['ev' => $this->resource->getTableName($mainTable)], ['entity_id', 'value'])
                ->where('ev.attribute_id = ?', $this->getAttributeId($attribute))
                ->where('ev.store_id = ?', $sid)
                ->where('ev.entity_id IN(?)', $productIds)
                ->order('ev.store_id')
                ->group('ev.entity_id');

            if ($this->getPkField() == 'row_id') {
                $query = $this->resource->getConnection()
                    ->select()
                    ->from(['e' => $this->resource->getTableName('catalog_product_entity')], ['entity_id'])
                    ->joinLeft(['ev' => $this->resource->getTableName($mainTable)], 'e.row_id = ev.row_id', ['value'])
                    ->where('ev.attribute_id = ?', $this->getAttributeId($attribute))
                    ->where('ev.store_id = ?', $sid)
                    ->where('e.entity_id IN(?)', $productIds)
                    ->order('ev.store_id')
                    ->group('e.entity_id');
            }

            $data = $this->resource->getConnection()->fetchPairs($query);

            foreach ($data as $productId => $value) {
                if ($map[$productId] == '' || $map[$productId] == 'no_selection') {
                    $map[$productId] = $value;
                }
            }
        }

        return $map;
    }

    private function getAttributeId(string $attributeCode): int
    {
        if (count($this->attributeIds) == 0) {
            $this->attributeIds = $this->resource->getConnection()->fetchPairs(
                $this->resource->getConnection()->select()
                    ->from(['ea' => $this->resource->getTableName('eav_attribute')], ['attribute_code', 'attribute_id'])
                    ->joinLeft(
                        [
                        'eet' => $this->resource->getTableName('eav_entity_type')],
                        'eet.entity_type_id = ea.entity_type_id',
                        []
                    )
                    ->where('eet.entity_type_code = ?', 'catalog_product')
            );
        }

        return (int)$this->attributeIds[$attributeCode];
    }

    private function getPkField(): string
    {
        if ($this->pkField == '') {
            $this->pkField = (string)$this->resource->getConnection()
            ->getAutoIncrementField($this->resource->getTableName('catalog_product_entity'));
        }

        return $this->pkField;
    }
}
