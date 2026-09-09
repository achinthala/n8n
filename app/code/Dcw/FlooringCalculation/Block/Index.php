<?php
declare(strict_types=1);

namespace Dcw\FlooringCalculation\Block;

use Dcw\FlooringCalculation\Helper\Data;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;

class Index extends Template
{
    public function __construct(
        Context $context,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Data $helperData,
        private readonly Attribute $attributeResourceModel,
        private readonly CollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function flooringCalculationHelper()
    {
        return $this->helperData;
    }

    public function attributeResourceModel()
    {
        return $this->attributeResourceModel;
    }

    public function getCbcTiles(ProductInterface $product)
    {
        $_products = $this->productCollectionFactory->create();
        $_products->addAttributeToSelect('*')
            ->addAttributeToFilter(
                'sku',
                [
                    'in' => [
                        $product->getSku() . '_corner', $product->getSku() . '_border', $product->getSku() . '_center'
                    ]
                ]
            )->addAttributeToFilter('status', 1);

        $cbcItemsArr = [];
        foreach ($_products as $_product) {
            $cbcItemArr = [];
            $cbc_type = str_replace([$product->getSku(), '_', '-'], '', $_product->getSku());
            $cbc_type = strtolower($cbc_type);
            $cbcItemArr['cbc_type'] = $cbc_type;
            $cbcItemArr['cbc_product_id'] = $_product->getId();
            $cbcItemArr['cbc_product_name'] = $_product->getName();
            $cbcItemArr['cbc_product_price'] = $_product->getPrice();
            $cbcItemsArr[] = $cbcItemArr;
        }
        return $cbcItemsArr;
    }

    public function getBestSellerItems($_category)
    {
        $best_seller_item_arr = [];
        $display_best_seller_item_arr = [];

        $_products = $this->productCollectionFactory->create();
        $_products->addAttributeToSelect(['id', 'revenue_ranking'])
            ->addCategoryFilter($_category)
            ->addAttributeToFilter('visibility', 4)
            ->addAttributeToFilter('status', 1);

        foreach ($_products as $i => $product) {
			$ranking = (float) $product->getRevenueRanking();
            if ($ranking > 0) { // === sort_by_bestseller
                $best_seller_item_arr[$product->getId()] = (float)$product->getRevenueRanking();
            }
        }

        arsort($best_seller_item_arr, SORT_NUMERIC);

        $best_seller_number = 1;
        foreach ($best_seller_item_arr as $bestSellerItemId => $bestSellerItemValue) {
            $display_best_seller_item_arr[$bestSellerItemId] = $best_seller_number;
            $best_seller_number++;
            if ($best_seller_number >= 6) {
                break;
            }
        }

        return $display_best_seller_item_arr;
    }

    public function getSwatchImageProducts($configProduct)
    {
        $arr = [];
        if ($configProduct->getTypeId() == 'configurable') {
            $_children = $configProduct->getTypeInstance()->getUsedProducts($configProduct);
            foreach ($_children as $child) {
                $arr[$child->getId()] = $child->getIncstoresPimSwatchImageSkuUrl1();
            }
        }
        return $arr;
    }

    public function getCalculaterType($configProduct)
    {
        $calculatorArr = [];
        if ($configProduct->getTypeId() == 'configurable') {
            $_children = $configProduct->getTypeInstance()->getUsedProducts($configProduct);
            foreach ($_children as $child) {
                if ($child->getStatus() == Status::STATUS_ENABLED) {
                    $calculatorType = $child->getAttributeText('incstores_pim_calculator_type');
                    if (!empty($child->getIncstoresPimCalculatorType()) && $calculatorType != 'none') {
                        $calculator_type = $calculatorType;
                    } else {
                        $calculator_type = 'none';
                    }
                    $calculatorArr[] = $calculator_type;
                }
            }
        }
        return $calculatorArr;
    }

    /** START - Ram - 10th Aug 2024  */
    public function getConfigValue($path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }
    /** END - Ram - 10th Aug 2024 */
}
