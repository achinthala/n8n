<?php

declare(strict_types=1);

namespace Dcw\FlooringCalculation\Observer;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class UpdateBackorderStatus implements ObserverInterface
{
    private const ATTRIBUTE_CODE = 'incstores_pim_can_backorder';

    public function __construct(
        private readonly StockRegistryInterface $stockRegistry,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var Product|null $product */
        $product = $observer->getEvent()->getProduct();
        if (!$product || !$product->getId()) {
            return;
        }

        try {
            $attributeValue = (int) $product->getData(self::ATTRIBUTE_CODE);
            $stockItem = $this->stockRegistry->getStockItem($product->getId());

            if ($attributeValue > 0) {
                $stockItem->setUseConfigBackorders(false);
                $backorderMode = Stock::BACKORDERS_YES_NOTIFY;
                $stockItem->setBackorders($backorderMode);
                if ($stockItem->getStockStatusChangedAutomaticallyFlag() == true) {
                    $stockItem->setIsInStock(true);
                }
            } else {
                $stockItem->setUseConfigBackorders(false);
                $stockItem->setBackorders(Stock::BACKORDERS_NO);
            }

            $this->stockRegistry->updateStockItemBySku($product->getSku(), $stockItem);
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'Unable to update backorder settings for product ID %s: %s',
                    $product->getId(),
                    $exception->getMessage()
                )
            );
        }
    }
}

