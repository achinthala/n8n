<?php

declare(strict_types=1);

namespace Dcw\CustomAttribute\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

class Data implements ArgumentInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * function name: getDetailsAttributeHtml
     * @param productId $productId
     */
    public function getDetailsAttributeHtml($productId)
    {
        $detailsHtml = '';
        $hasDetails = false;

        try {
            // Load the product by SKU
            $product = $this->productRepository->getById($productId);
            $attributes = $product->getAttributes();
            $getDetailAttributesList = $this->getConfigValue('dcw_product_tab/tabs/show_indec_attributes');
            
            if ($getDetailAttributesList) {
                $detailAttributesListArray = explode(',', $getDetailAttributesList);
                $detailsHtml = '<h3 class="text-size18 font-semibold mb-4">Details</h3>';
                $detailsHtml.= "<ul>";
            } else {
                $detailAttributesListArray = [];
            }

            foreach ($attributes as $attribute) {
                if (in_array($attribute->getAttributeCode(), $detailAttributesListArray) 
                && $attribute->getFrontend()->getValue($product)) {
                    $hasDetails = true;
                    $detailsHtml.='<li>';
                    $detailsHtml.='<label>'.$attribute->getStoreLabel().'</label>';
                    $detailsHtml.='<span>'.$attribute->getFrontend()->getValue($product).'</span>';
                    $detailsHtml.='</li>';
                }
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->info('An error occurred: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->info('An error occurred: ' . $e->getMessage());
        }

        if (!$hasDetails) {
            $detailsHtml = '';
        } else {
            $detailsHtml.= "</ul>";
        }

        return $detailsHtml;
    }

    /**
     * function name: getDimensionsAttributeHtml
     * @param productId $productId
     */
    public function getDimensionsAttributeHtml($productId)
    {
        $dimensionsHtml = '';
        $hasDetails = false;

        try {
            // Load the product by SKU
            $product = $this->productRepository->getById($productId);
            $attributes = $product->getAttributes();
            $getDimensionsAttributesList = $this->getConfigValue('dcw_product_tab/tabs/size_attributes');
            
            if ($getDimensionsAttributesList) {
                $dimensionsAttributesListArray = explode(',', $getDimensionsAttributesList);
                $dimensionsHtml = '<h3 class="text-size18 font-semibold mb-4">Dimensions</h3>';
                $dimensionsHtml.= "<ul>";
            } else {
                $dimensionsAttributesListArray = [];
            }

            foreach ($attributes as $attribute) {
                if (in_array($attribute->getAttributeCode(), $dimensionsAttributesListArray) 
                && $attribute->getFrontend()->getValue($product)) {
                    $hasDetails = true;
                    $dimensionsHtml.='<li>';
                    $dimensionsHtml.='<label>'.$attribute->getStoreLabel().'</label>';
                    $dimensionsHtml.='<span>'.$attribute->getFrontend()->getValue($product).'</span>';
                    $dimensionsHtml.='</li>';
                }
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->info('An error occurred: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->info('An error occurred: ' . $e->getMessage());
        }

        if (!$hasDetails) {
            $dimensionsHtml = '';
        } else {
            $dimensionsHtml.= "</ul>";
        }

        return $dimensionsHtml;
    }
    /**
     * function name: getConfigValue
     * @param $configVariable
     */
    public function getConfigValue($configVariable)
    {
        return $this->scopeConfig->getValue($configVariable, ScopeInterface::SCOPE_STORE);
    }
    /**
     * function name: getProductDeatils
     * @param productId $productId
     */
    public function getProductDeatils($productId)
    {
        $data['productDetails'] = $this->getDetailsAttributeHtml($productId);
        $data['dimensionsHtml'] = $this->getDimensionsAttributeHtml($productId);

        return $data;
    }
    
}
