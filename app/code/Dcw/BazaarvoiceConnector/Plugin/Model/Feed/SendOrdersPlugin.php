<?php

declare(strict_types=1);

namespace Dcw\BazaarvoiceConnector\Plugin\Model\Feed;

use Bazaarvoice\Connector\Model\Feed\PurchaseFeed;
use Dcw\BazaarvoiceConnector\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type;

class SendOrdersPlugin
{

    /**
     * A sample product start with "S" inside the sku.
     */
    private const PATTERN = '/^S/i';

    /**
     * Attribute set for samples
     */
    private const ATTRIBUTE_SET_ID_FOR_SAMPLES = 455;

    /**
     * @param Config $config
     * @param ConfigProviderInterface $configProvider
     */
    public function __construct(
        private readonly Config $config,
        private readonly ConfigProviderInterface $configProvider
    ) {
    }

    /**
     * Checks if a product is a sample
     * @param Product $product
     * @return bool
     */
    private function isSampleProduct(Product $product) : bool
    {
        if (preg_match(self::PATTERN, $product->getSku()) && $product->getAttributeSetId() == self::ATTRIBUTE_SET_ID_FOR_SAMPLES) {
            return true;
        }
        return false;
    }

    /**
     * Search sample products in orders
     * @param Collection $orders
     * @return Collection
     */
    private function searchSampleItems(Collection $orders)
    {
        foreach ($orders as $orderId => $order) {

            if ($this->configProvider->isFamiliesEnabled()) {
                $items = $order->getAllItems();
            } else {
                $items = $order->getAllVisibleItems();
            }

            $sampleItems = 0;
            $configurableItems = 0;
            foreach ($items as $item) {

                $product = $item->getProduct();
                $isSample = $this->isSampleProduct($product);
                // If product is sample we set as disable so the sendOrder method will skip this product.
                if ($isSample) {
                    if ($item->getProductType() != Type\Configurable::TYPE_CODE) {
                        $product->setStatus(Status::STATUS_DISABLED);
                        $item->setProduct($product);
                        $sampleItems++;
                    }

                    if ($item->getProductType() == Type\Configurable::TYPE_CODE) {
                        $configurableItems++;
                    }
                }
            }
            // If all products are sample, then remove the order.
            if (count($items) > 0 && $sampleItems == count($items) - $configurableItems) {
                $orders->removeItemByKey($orderId);
            }
        }
        return $orders;
    }

    /**
     * @param PurchaseFeed $subject
     * @param Collection $orders
     * @param StoreInterface|Store $store
     * @param string $purchaseFeedFileName
     * @return array
     */
    public function beforeSendOrders(PurchaseFeed $subject, Collection $orders, $store, $purchaseFeedFileName): array
    {
        if ($this->config->isRemoveSamplesEnabled()) {
            $orders = $this->searchSampleItems($orders);
            return [$orders, $store, $purchaseFeedFileName];
        }
        return [$orders, $store, $purchaseFeedFileName];
    }
}
