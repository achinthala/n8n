<?php

declare(strict_types=1);

namespace Dcw\Spotlight\Cron;

use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Dcw\Spotlight\Model\SpotlightUrlFactory;
use Dcw\Spotlight\Model\ResourceModel\SpotlightUrl\CollectionFactory as SpotlightCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Zend_Log;
use Zend_Log_Writer_Stream;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;

class SpotlightUrl
{
    /**
     * @var Filesystem
     */
    protected $filesystem;
    /**
     * @var Configurable
     */
    protected $configurable;
    /**
     * @var ProductFactory
     */
    protected $productFactory;
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;
    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepositoryInterface;
	/**
     * @var UrlRewriteCollectionFactory
     */
    protected $urlRewriteCollectionFactory;
	/**
     * @var ProductRepositoryInterface
     */
    protected $productRepositoryInterface;
    /**
     * @var SpotlightUrlFactory
     */
    protected $spotlightUrlFactory;
    /**
     * @var SpotlightCollectionFactory
     */
    protected $spotlightCollectionFactory;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var UrlInterface
     */
    protected $url;
    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    public function __construct(
        Filesystem  $filesystem,
        Configurable $configurable,
        ProductFactory $productFactory,
        CollectionFactory $productCollectionFactory,
        CategoryRepositoryInterface $categoryRepositoryInterface,
		ProductRepositoryInterface $productRepositoryInterface,
        SpotlightUrlFactory $spotlightUrlFactory,
        SpotlightCollectionFactory $spotlightCollectionFactory,
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        UrlInterface $url,
        CategoryCollectionFactory $categoryCollectionFactory,
		UrlRewriteCollectionFactory $urlRewriteCollectionFactory
    )
    {
        $this->filesystem = $filesystem;
        $this->configurable = $configurable;
        $this->productFactory = $productFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryRepositoryInterface = $categoryRepositoryInterface;
		$this->productRepositoryInterface = $productRepositoryInterface;
        $this->spotlightUrlFactory = $spotlightUrlFactory;
        $this->spotlightCollectionFactory = $spotlightCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->url = $url;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->urlRewriteCollectionFactory = $urlRewriteCollectionFactory;
    }

    /**
     * insert the spotlight data into tmp table
     */
    public function execute()
    {
        $writer = new Zend_Log_Writer_Stream(BP . '/var/log/spotlight_urls.log');
        $logger = new Zend_Log();
        $logger->addWriter($writer);

        // Load first 200 products from the tmp table.
        $collection = $this->spotlightCollectionFactory->create();
        $collection->addFieldToFilter('status', ['eq' => 'Pending']);
        $collection->addFieldToFilter('type', ['eq' => 'Main']);
        $collection->addOrder('id', 'ASC');
        $collection->setPageSize(200);

        $spotlightProducts = $collection->getData();

        if (count($spotlightProducts) < 1) {
            $logger->info("The collection is empty.");
        }

        $storeId = $this->storeManager->getStore()->getId();

        $logger->info("storeId=====".$storeId);

        foreach ($spotlightProducts as $spotlightData) {
            $spotLightId = $spotlightData['id'];
            $productId = $spotlightData['product_id'];
            $loadSpotLight = $this->spotlightUrlFactory->create()->load($spotLightId);

            try {
                $storeId = 1;
				$product = $this->productFactory->create();
				$product->setStoreId($storeId);
				$product->load($productId);

                $productSku = $product->getSku();
                $productName = $product->getName();
                $categoryIds = $product->getCategoryIds();
                $productType = $product->getTypeId();

                $this->url->setScope($storeId);
				
				if (in_array(2189,$categoryIds)){
					$this->productUrlRubberTiles($product, $storeId);
					$status = 'Success';
					$errorMsg = 'Spotlight urls generated successfully';
				} else{
					if (!empty($categoryIds)) {
						$category = $this->getFirstEnabledCategory($categoryIds, $storeId);

						if ($category) {
							$brandName = $this->getBrandNameFromCategories($categoryIds, $storeId);

							if ($productType == 'configurable') {
								$configurableProductId = $product->getId();
								$childProductIds = $this->configurable->getChildrenIds($configurableProductId);
								$childProductSku = $productSku;

								if ((int)$product->getStatus() === ProductStatus::STATUS_ENABLED) {
									$configAvailability = 'in_stock';
								} else {
									$configAvailability = 'out_of_stock';
								}

								foreach ($childProductIds as $ids) {
									foreach ($ids as $childId) {
										$childProduct = $this->productFactory->create()->load($childId);
										$childProductSku = $childProduct->getSku();
										$childProductName = $childProduct->getName();
										$categorySpotLightUrls = $category->getUrl().'?spotlight='.$childProductSku;

										if ((int)$childProduct->getStatus() === ProductStatus::STATUS_ENABLED) {
											$childAvailability = 'in_stock';
										} else {
											$childAvailability = 'out_of_stock';
										}

										if ($configAvailability == 'out_of_stock') {
											$childAvailability = 'out_of_stock';
										}
										
										//spotlight data will insert into the tmp table here
										$this->insertDataIntoTable($childProductSku, $childProductName, $brandName, $categorySpotLightUrls, 'SpotLight', $childAvailability);
									}
								}

								$categorySpotLightUrls = $category->getUrl().'?spotlight='.$childProductSku;

								//spotlight data will insert into the tmp table here
								$this->insertDataIntoTable($productSku, $productName, $brandName, $categorySpotLightUrls, 'SpotLight', $configAvailability);
								$status = 'Success';
								$errorMsg = 'Spotlight urls generated successfully';
							} else {
								if ((int)$product->getStatus() === ProductStatus::STATUS_ENABLED) {
									$mainAvailability = 'in_stock';
								} else {
									$mainAvailability = 'out_of_stock';
								}

								$categorySpotLightUrls = $category->getUrl().'?spotlight='.$productSku;

								//spotlight data will insert into the tmp table here
								$this->insertDataIntoTable($productSku, $productName, $brandName, $categorySpotLightUrls, 'SpotLight', $mainAvailability);
								$status = 'Success';
								$errorMsg = 'Spotlight urls generated successfully';
							}
						} else {
							$status = 'Error';
							$errorMsg = "Category not found for product ID: " . $productId. ' Json: ' . json_encode($categoryIds);
						}
					} else {
						$status = 'Error';
						$errorMsg = "No categories found for product ID: " . $productId;
					}
			}
            } catch (Exception $e) {
                $status = 'Error';
                $errorMsg = "spotlightCollectionFactory : " . $e->getMessage();
                $logger->info("spotlightCollectionFactory ".$e->getMessage());
            }

            $loadSpotLight->setStatus($status);
            $loadSpotLight->setResponse($errorMsg);
            $loadSpotLight->save();

            $logger->info("Spotlight urls generated");
        }
    }

    /**
     * products ids will insert into tmp table here
     */
    public function insertProductIds()
    {
        $writer = new Zend_Log_Writer_Stream(BP . '/var/log/spotlight_productdata_insert.log');
        $logger = new Zend_Log();
        $logger->addWriter($writer);

        //this will truncate the tmp table data
        $this->truncateTmpTable();

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToSelect('entity_id');
        //$productCollection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        $productCollection->addAttributeToFilter(
            'visibility',
            ['in' => [
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG, // Products visible in catalog
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH,   // Products visible in search
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH   // Products visible in both
            ]]
        );

        foreach ($productCollection as $product) {
            $productId = $product->getId();

            $spotlightTable = $this->spotlightUrlFactory->create();
            
            // Set data
            $spotlightTable->setData('product_id', $productId);
    
            // Save data
            try {
                $spotlightTable->save();
            } catch (Exception $e) {
                $logger->info("Insert error ".$e->getMessage());
            }
        }
    }

    /** 
     * spotlight data will insert into the tmp table here
     */
    public function insertDataIntoTable($sku, $name, $brand, $spotlightUrl, $type, $availability)
    {
        $spotlightTable = $this->spotlightUrlFactory->create();
            
        // Set data
        $spotlightTable->setData('sku', $sku);
        $spotlightTable->setData('name', $name);
        $spotlightTable->setData('brand', $brand);
        $spotlightTable->setData('spotlight_url', $spotlightUrl);
        $spotlightTable->setData('type', $type);
        $spotlightTable->setData('availability', $availability);

        // Save data
        try {
            $spotlightTable->save();
        } catch (Exception $e) {
            $logger->info("configurable insert error ".$e->getMessage());
        }
    }

    /**
     * spotlight data will add into csv here
     */
    public function addSpotlightDataIntoCSV()
    {
        $mediapath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
        $importSheet = "supplemental_feed.csv";
        $filePath = $mediapath . '/import/' .$importSheet;

        // Open a file in write mode ('w')
        $csvFile = fopen($filePath, 'w');

        // Add the header row
        fputcsv($csvFile, ['id', 'title', 'brand', 'link']);

        $collection = $this->spotlightCollectionFactory->create();
        $collection->addFieldToFilter('type', ['eq' => 'SpotLight']);
        $collection->addOrder('id', 'ASC');

        $spotlightProducts = $collection->getData();

        foreach ($spotlightProducts as $spotlightData) {
            $productSku = $spotlightData['sku'];
            $productName = $spotlightData['name'];
            $brandName = $spotlightData['brand'];
            $categorySpotLightUrls = $spotlightData['spotlight_url'];
            // Write the data to the CSV file
            fputcsv($csvFile, [$productSku, $productName, $brandName, $categorySpotLightUrls]);
        }

        fclose($csvFile);

        //once the csv generated truncate the data from tmp table
        $this->truncateTmpTable();
    }

    /**
     * truncate tmp table
     */
    public function truncateTmpTable()
    {
        // Get the connection
        $connection = $this->resourceConnection->getConnection();

        // Table name
        $tableName = $this->resourceConnection->getTableName('dcw_spotlight_tmp');

        // Truncate the table
        $connection->truncateTable($tableName);
    }

    /**
     * Get the first enabled category from the provided category IDs.
     *
     * @param array $categoryIds
     * @return \Magento\Catalog\Model\Category|null
     */
    public function getFirstEnabledCategory($categoryIds, $storeId)
    {
        if (empty($categoryIds)) {
            return null;
        }

        // Load categories with the specified IDs and filter by active status
        $collection = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect(['name', 'is_active', 'url_path', 'display_mode', 'exclude_from_spotlight'])
            ->addAttributeToFilter('entity_id', ['in' => $categoryIds])
            ->addAttributeToFilter('is_active', 1);

        foreach ($categoryIds as $categoryId) {
            $category = $collection->getItemById($categoryId);

            // Skip if category doesn't exist or has PAGE display mode
            if (!$category || $category->getDisplayMode() === 'PAGE') {
                continue;
            }
            
            // Check if category or any parent is excluded from spotlight URLs
            // This checks the entire hierarchy - if a parent is excluded, all children inherit the exclusion
            if ($this->isCategoryOrParentExcludedFromSpotlight($categoryId)) {
                continue;
            }
            
            // Return the first valid category
            return $this->categoryRepositoryInterface->get($categoryId, $storeId);
        }

        return null;
    }

    /**
     * Get brand name from product's categories.
     * Returns the first category name where is_brand == 1, or "Flooring Inc" if none found.
     *
     * @param array $categoryIds
     * @param int $storeId
     * @return string
     */
    public function getBrandNameFromCategories(array $categoryIds, int $storeId): string
    {
        if (empty($categoryIds)) {
            return 'Flooring Inc';
        }

        $collection = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect(['name', 'is_active', 'url_path', 'display_mode', 'exclude_from_spotlight', 'is_brand'])
            ->addAttributeToFilter('entity_id', ['in' => $categoryIds])
            ->addAttributeToFilter('is_active', 1)
            ->setStoreId($storeId);

        foreach ($categoryIds as $categoryId) {
            $category = $collection->getItemById($categoryId);

            if (!$category) {
                continue;
            }

            if ((int) $category->getData('is_brand') === 1) {
                return $category->getName();
            }
        }

        return 'Flooring Inc';
    }

    /**
     * Check if a category or any of its parent categories is excluded from Spotlight URLs
     * 
     * This method checks the entire category hierarchy. If any parent category has
     * exclude_from_spotlight = 1, all child categories inherit this exclusion.
     * 
     * @param int $categoryId
     * @return bool True if category or any parent is excluded, false otherwise
     */
    public function isCategoryOrParentExcludedFromSpotlight($categoryId)
    {
        try {
            // Load the category
            $category = $this->categoryRepositoryInterface->get($categoryId);
            
            if (!$category) {
                return false;
            }
            
            // Check if the current category itself is excluded
            if ($category->getData('exclude_from_spotlight') == 1) {
                return true;
            }
            
            // Get the category path (e.g., "1/2/1826/1853/1871")
            $categoryPath = $category->getPath();
            $pathArray = explode('/', $categoryPath);
            
            // Remove default category IDs (1 and 2) and the current category
            $filteredPath = array_filter($pathArray, function($id) use ($categoryId) {
                return $id != '1' && $id != '2' && $id != $categoryId;
            });
            
            // Check each parent category
            foreach ($filteredPath as $parentId) {
                try {
                    $parentCategory = $this->categoryRepositoryInterface->get($parentId);
                    
                    // If any parent is excluded, this category is also excluded
                    if ($parentCategory && $parentCategory->getData('exclude_from_spotlight') == 1) {
                        return true;
                    }
                } catch (Exception $e) {
                    // Skip this parent if it can't be loaded
                    continue;
                }
            }
            
            return false;
        } catch (Exception $e) {
            // Log error if needed
            return false;
        }
    }
	public function productUrlRubberTiles($product, $storeId)
    {
		$writer = new Zend_Log_Writer_Stream(BP . '/var/log/rubbertiles.log');
        $logger = new Zend_Log();
        $logger->addWriter($writer);
		$product->setCategoryId(2189);
        $productUrl = $product->getProductUrl();
		$productId = $product->getId();
		$logger->info("ProductId=====".$product->getId());
		$logger->info("Product Url=====".$productUrl);
		$rewrite = $this->urlRewriteCollectionFactory->create()
			->addFieldToFilter('entity_type', 'product')
			->addFieldToFilter('entity_id', $productId)
			->addFieldToFilter('store_id', $storeId)
			->addFieldToFilter('target_path', ['like' => '%category/2189%'])
			->getFirstItem();

		if ($rewrite->getId()) {
			$productUrl = $this->storeManager->getStore($storeId)->getBaseUrl()
				. $rewrite->getRequestPath();
			$logger->info("Product Url1=====".$productUrl);
		}
		
		$productType = $product->getTypeId();
		$categoryIds = $product->getCategoryIds();
		$brandName = $this->getBrandNameFromCategories($categoryIds, $storeId);
		if ($productType == 'configurable') {
			$configurableProductId = $product->getId();
			$childProductIds = $this->configurable->getChildrenIds($configurableProductId);

			if ((int)$product->getStatus() === ProductStatus::STATUS_ENABLED) {
				$configAvailability = 'in_stock';
			} else {
				$configAvailability = 'out_of_stock';
			}

			foreach ($childProductIds as $ids) {
				foreach ($ids as $childId) {
					$childProduct = $this->productFactory->create()->load($childId);
					$childProductSku = $childProduct->getSku();
					$childProductName = $childProduct->getName();
					$categorySpotLightUrls = $productUrl.'?option='.$childProductSku;
					$logger->info("categorySpotLightUrls=====".$categorySpotLightUrls);

					if ((int)$childProduct->getStatus() === ProductStatus::STATUS_ENABLED) {
						$childAvailability = 'in_stock';
					} else {
						$childAvailability = 'out_of_stock';
					}

					if ($configAvailability == 'out_of_stock') {
						$childAvailability = 'out_of_stock';
					}
					
					//spotlight data will insert into the tmp table here
					$this->insertDataIntoTable($childProductSku, $childProductName, $brandName, $categorySpotLightUrls, 'SpotLight', $childAvailability);
				}
			}
		}
    }
}
