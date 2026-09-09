<?php
declare(strict_types=1);

namespace Dcw\FlooringCalculation\Block;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Registry;
use Magento\Catalog\Helper\Image;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Swatches\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class Quickship extends Template
{
    public function __construct(
        Context $context,
        private readonly Data $swatchHelper,
        private readonly ProductFactory $productFactory,
        private readonly FormKey $formKey,
        private readonly Registry $registry,
        private readonly Image $imagehelper,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
    }

    /* Get Hashcode of Visual swatch by option id */
    public function getAtributeSwatchHashcode($optionid)
    {
        $hashcodeData = $this->swatchHelper->getSwatchesByOptionsId([$optionid]);
        return $hashcodeData[$optionid]['value'];
    }

    /**
     * Get Form Key
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * @param int $id
     * @return ProductInterface|Product
     */
    public function getLoadProduct($id)
    {
        try {
            return $this->productRepository->getById((int)$id);
        } catch (NoSuchEntityException $exception) {
            $this->_logger->error($exception->getTraceAsString(), [$id]);

            return $this->productFactory->create();
        }
    }

    /**
     * @param string $sku
     * @return ProductInterface|Product
     */
    public function getProductBySku($sku)
    {
        try {
            return $this->productRepository->get($sku);
        } catch (NoSuchEntityException $exception) {
            $this->_logger->error($exception->getTraceAsString(), [$sku]);

            return $this->productFactory->create();
        }
    }

    /**
     *
     * @param integer $id for its quickship product
     * @return array
     */

    public function getQuickShipProduct($product)
    {
        $quickshipids[] = [];
        $allowedQuickShipColorIds = [];
        $quickShipOptionsArr = [];

        $is_quickship_color_count_same = 0;
        $quickShipOptionsArr = [];
        $colorOptionsArr = [];
        //$product = $this->getLoadProduct($id);
        $childIds = [];

        $getQuickShipData = [];

        if ($product) {
            if ($product->getIncstoresPimShippingProgram()) {
                $quickshipids[] = $product->getIncstoresPimShippingProgram();
            }
            if ($product->getTypeId() == 'configurable') {
                $_children = $product->getTypeInstance()->getUsedProducts($product);

                foreach ($_children as $child) {
                    $childIds[] = $child->getId();
                }

                if (is_array($childIds) && count($childIds) > 0) {
                    $loadProducts = $this->loadProductsByIds($childIds);
                    if (is_array($loadProducts) && count($loadProducts) > 0) {
                        foreach ($loadProducts as $productData) {
							if ($productData->getStatus() == 1){
                            $colorOptionsArr[$productData->getIncstoresPimColorAxis()] = $productData->getIncstoresPimColorAxis();

                            $adminStoreId = Store::ADMIN_CODE;
                            $this->storeManager->setCurrentStore($adminStoreId);
                            //getQuickShipProduct start
                            $shippingProgramText = (string)$productData->getAttributeText('incstores_pim_shipping_program');
                            $shippingProgramparts = explode('|', $shippingProgramText);

                            if (count($shippingProgramparts) >= 2) {
                                $shippingProgram = $shippingProgramparts[0];
                            } else {
                                $shippingProgram = '';
                            }

                            if ($productData) {
                                $getIncstoresPimColorAxis = $productData->getIncstoresPimColorAxis();

                                if (!empty($productData->getIncstoresPimShippingProgram()) && $shippingProgram != 'none' &&
                                    $shippingProgram != 'free_ship' && !empty($getIncstoresPimColorAxis)) {
                                    $allowedQuickShipColorIds[] = $getIncstoresPimColorAxis; //getQuickShipProduct
                                    $quickShipOptionsArr[$getIncstoresPimColorAxis] = $getIncstoresPimColorAxis; //isQuickShipColorCountSame
                                }

                            }

                            $storeId = $this->storeManager->getDefaultStoreView()->getId();
                            $this->storeManager->setCurrentStore($storeId);
					    }
						}
                    }

                    if ($colorOptionsArr == $quickShipOptionsArr) {
                        $is_quickship_color_count_same = 1;
                    }
                }
            }
        }

        $getQuickShipData['allowedQuickShipColorIds'] = array_values(array_unique($allowedQuickShipColorIds));
        $getQuickShipData['is_quickship_color_count_same'] = $is_quickship_color_count_same;

        return $getQuickShipData;
    }

    /**
     * QuickShip color count & normal color count same nor not checking
     */

    public function isQuickShipColorCountSame($product)
    {
        $is_quickship_color_count_same = 0;
        $quickShipOptionsArr = [];
        $colorOptionsArr = [];
        //$product = $this->getLoadProduct($id);

        if ($product) {
            $_children = $product->getTypeInstance()->getUsedProducts($product);
            $childIds = [];

            foreach ($_children as $child) {
                $childIds[] = $child->getId();
            }

            $loadProducts = $this->loadProductsByIds($childIds);

            if (is_array($loadProducts) && count($loadProducts) > 0) {
                foreach ($loadProducts as $productData) {
                    if ((int) $productData->getStatus() !== 1) {
                        continue;
                    }
                    $colorOptionsArr[$productData->getIncstoresPimColorAxis()] = $productData->getIncstoresPimColorAxis();
                    $adminStoreId = Store::ADMIN_CODE;
                    $this->storeManager->setCurrentStore($adminStoreId);
                    $shippingProgramText = (string)$productData->getAttributeText('incstores_pim_shipping_program');
                    $shippingProgramparts = explode('|', $shippingProgramText);
                    if (count($shippingProgramparts) >= 2) {
                        $shippingProgram = $shippingProgramparts[0];
                    } else {
                        $shippingProgram = '';
                    }
                    if (!empty($productData->getIncstoresPimShippingProgram()) && $shippingProgram != 'none' &&
                        $shippingProgram != 'free_ship' && !empty($productData->getIncstoresPimColorAxis())) {
                        $quickShipOptionsArr[$productData->getIncstoresPimColorAxis()] = $productData->getIncstoresPimColorAxis();
                    }
                    $storeId = $this->storeManager->getDefaultStoreView()->getId();
                    $this->storeManager->setCurrentStore($storeId);
                }
            }

            if ($colorOptionsArr == $quickShipOptionsArr) {
                $is_quickship_color_count_same = 1;
            }
        }

        return $is_quickship_color_count_same;
    }


    /**
     * Send Media url
     *
     * @return string media url
     */
    public function getMediaDirectoryUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Get product image url
     *
     * @param \Magento\Catalog\Model\ProductFactory $_product
     * @param string $type for image type
     * @return string
     */

    public function getProductImageUrl($_product, $type)
    {
        return $this->imagehelper->init($_product, $type)->getUrl();
    }

    /**
     * Get currency product id
     *
     * @return string
     */

    public function getCurrentProduct()
    {
        $product = $this->registry->registry('current_product');
        if ($product) {
            return $product->getId();
        }
        return false;
    }

    /**
     * load products by product id's
     *
     * @param array $productIds
     */
    public function loadProductsByIds($productIds)
    {
        $productData = [];
        $productCollection = $this->collectionFactory->create()
            ->addAttributeToSelect(
                [
                    'entity_id',
                    'name',
                    'sku',
                    'incstores_pim_variant_axis',
                    'incstores_pim_color_axis',
                    'incstores_pim_shipping_program',
					'status'
                ]
            )
            ->addIdFilter($productIds)
            ->load();

        foreach ($productCollection as $product) {
            $productData[] = $product;
        }

        return $productData;
    }
}
