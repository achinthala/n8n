<?php
declare(strict_types=1);

namespace Dcw\ShoppingCart\ViewModel;

use Exception;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Model\ProductRepository;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Data implements ArgumentInterface
{
    public function __construct(
        private readonly StockRegistryInterface $stockRegistry,
        private readonly ProductRepository $productRepository,
        private readonly LoggerInterface $logger,
		private readonly TimezoneInterface $timezoneInterface,
        private readonly RequestInterface $request
    ) {
    }

    public function getProductPromiseDate($productId, $requestedQty)
    {
        try {
            // Load product
			$product = $this->productRepository->getById($productId);

            // Get stock item
            $stockItem = $this->stockRegistry->getStockItem($product->getId());

            $availableQty = $stockItem->getQty();
            $isBackorderAllowed = $stockItem->getBackorders(); // 0 = No Backorders, 1 = Allow Qty Below 0, 2 = Allow Qty Below 0 and Notify Customer

            // Check is backorder enabled & Allow Qty Below 0 and Notify Customer set
            if ($requestedQty <= $availableQty) {
                return false; // The requested quantity is available
            } elseif ($isBackorderAllowed) {
                return $product->getIncstoresPimPromiseDate();
            } else {
                return false; // The requested quantity exceeds available stock, and backorders are not allowed
            }
		} catch (Exception $e) {
			$this->logger->error($e->getMessage());

            return false;
		}
    }

    public function getProductPromiseDateFromProductObject($product, $requestedQty)
    {
        try {
            // Get stock item
            $stockItem = $this->stockRegistry->getStockItem($product->getId());

            $availableQty = $stockItem->getQty();
            $isBackorderAllowed = $stockItem->getBackorders(); // 0 = No Backorders, 1 = Allow Qty Below 0, 2 = Allow Qty Below 0 and Notify Customer

            // Check is backorder enabled & Allow Qty Below 0 and Notify Customer set
            if ($requestedQty <= $availableQty) {
                return false; // The requested quantity is available
            } elseif ($isBackorderAllowed) {
                return $product->getIncstoresPimPromiseDate();
            } else {
                return false; // The requested quantity exceeds available stock, and backorders are not allowed
            }
		} catch (Exception $e) {
			$this->logger->error($e->getMessage());
            
            return false;
		}
    }

    public function getFullControllerPath()
    {
        $moduleName = $this->request->getModuleName();
        $controllerName = $this->request->getControllerName();
        $actionName = $this->request->getActionName();

        return $moduleName . '/' . $controllerName . '/' . $actionName;
    }

    public function checkForQtyBackorder($product, $requestedQty)
    {
        $stockItem = $this->stockRegistry->getStockItem($product->getId());
        $availableQty = $stockItem->getQty();
        $isBackorderAllowed = $stockItem->getBackorders();

        if (($requestedQty > $availableQty) && $isBackorderAllowed > 0) {
            return true;
        }

        return false;
    }

	public function checkForQtyBackorderData($productId, $requestedQty)
    {
        $stockItem = $this->stockRegistry->getStockItem($productId);
        $availableQty = $stockItem->getQty();
        $isBackorderAllowed = $stockItem->getBackorders();
        $message='';
        if (($requestedQty > $availableQty) && $isBackorderAllowed > 1) {
            $message='Pre-orders will ship soon. Contact us at 866-416-6388 with any questions!';
        }

        return $message;
    }

	public function getCurrentDate()
    {
		return $this->timezoneInterface->date()->format('Y-m-d');
	}

	public function getProductBySku($sku)
    {
        try {
            $product = $this->productRepository->get($sku);
            return $product->getId();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null; // product not found
        }
    }

    public function getProductObjectBySku($sku)
    {
        try {
            return $this->productRepository->get($sku);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null; // product not found
        }
    }
}
