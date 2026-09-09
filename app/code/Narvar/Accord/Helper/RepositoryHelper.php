<?php

namespace Narvar\Accord\Helper;

use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollection;
use Magento\Customer\Model\ResourceModel\GroupRepository as GroupRepository;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Directory\Model\RegionFactory;
use Narvar\Accord\Helper\CacheHelper;

class RepositoryHelper
{
    private $productRepository;

    private $customerFactory;

    private $storeManager;

    private $categoryCollection;

    private $groupRepository;

    private $shipmentRepository;

    private $invoiceRepository;

    private $regionFactory;

    private $cacheHelper;

    private const NARVAR_REGION_CACHE_KEY  = 'narvar_accord_region_cache_';
    
    private const NARVAR_SOURCE_CACHE_KEY  = 'narvar_accord_source_cache_';

    public function __construct(
        ProductRepository $productRepository,
        CustomerFactory $customerFactory,
        StoreManager $storeManager,
        CategoryCollection $categoryCollection,
        GroupRepository $groupRepository,
        ShipmentRepositoryInterface $shipmentRepository,
        InvoiceRepositoryInterface $invoiceRepository,
        RegionFactory $regionFactory,
        CacheHelper $cacheHelper
    ) {
        $this->productRepository    = $productRepository;
        $this->customerFactory      = $customerFactory;
        $this->storeManager         = $storeManager;
        $this->categoryCollection   = $categoryCollection;
        $this->groupRepository      = $groupRepository;
        $this->shipmentRepository   = $shipmentRepository;
        $this->invoiceRepository    = $invoiceRepository;
        $this->regionFactory        = $regionFactory;
        $this->cacheHelper          = $cacheHelper;
    }

    public function getCustomer($customerId)
    {
        return $this->customerFactory->create()->load($customerId);
    }

    public function getProduct($productId)
    {
        return $this->productRepository->getById($productId);
    }

    public function getStore($storeId)
    {
        return $this->storeManager->getStore($storeId);
    }

    public function getCategories($categoryIds)
    {
        return $this->categoryCollection->create()
              ->addAttributeToSelect('name')
              ->addAttributeToFilter('entity_id', $categoryIds);
    }

    public function getCustomerGroup($customerGroupId)
    {
        return $this->groupRepository->getById($customerGroupId);
    }

    public function getShipmentDataBySearchCriteria($shipmentSearchCriteria)
    {
        try {
            $shipments = $this->shipmentRepository->getList($shipmentSearchCriteria);
            $shipmentRecords = $shipments->getItems();
        } catch (\Exception $exception) {
            $shipmentRecords = null;
        }
        return $shipmentRecords;
    }

    public function getInvoiceDataBySearchCriteria($invoiceSearchCriteria)
    {
        try {
            $invoices = $this->invoiceRepository->getList($invoiceSearchCriteria);
            $invoiceRecords = $invoices->getItems();
        } catch (\Exception $exception) {
            $invoiceRecords = null;
        }
        return $invoiceRecords;
    }

    public function getSourceData($sourceCode)
    {
        try {
            $cacheId = self::NARVAR_SOURCE_CACHE_KEY . $sourceCode;
            $sourceData = $this->cacheHelper->loadDataFromCache($cacheId);
            if (is_null($sourceData)) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $sourceRepository = $objectManager->get('Magento\InventoryApi\Api\SourceRepositoryInterface');
                $sourceData = $sourceRepository->get($sourceCode)->getData();
                $this->cacheHelper->saveDataInCache($cacheId, $sourceData);
            }
            return $sourceData;
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * Get Shipment data by Shipment Id
     *
     * @param $id
     *
     * @return ShipmentInterface|null
     */
    public function getShipmentById($id)
    {
        return $this->shipmentRepository->get($id);
    }

    /**
     * Get Region data by Region Id
     *
     * @param $id
     *
     * @return region data|null
     */
    public function getRegionById($regionId)
    {
        try {
            $cacheId = self::NARVAR_REGION_CACHE_KEY . $regionId;
            $regionData = $this->cacheHelper->loadDataFromCache($cacheId);
            if (is_null($regionData)) {
                $regionData = $this->regionFactory->create()->load($regionId)->getData();
                $this->cacheHelper->saveDataInCache($cacheId, $regionData);
            }
            return $regionData;
        } catch (\Exception $exception) {
                return null;
        }
    }
}
