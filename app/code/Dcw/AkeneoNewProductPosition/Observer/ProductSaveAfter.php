<?php
declare(strict_types=1);

namespace Dcw\AkeneoNewProductPosition\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class ProductSaveAfter implements ObserverInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var State
     */
    private $state;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     * @param State $state
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger,
        State $state
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->state = $state;
    }

    /**
     * Set position to end for newly assigned products in categories
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        
        if (!$product || !$product->getId()) {
            return;
        }
        if (!$product->isObjectNew()) {
            return;
        }
        $productId = $product->getId();
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('catalog_category_product');

        try {
            $select = $connection->select()
                ->from($tableName, ['category_id', 'position'])
                ->where('product_id = ?', $productId)
                ->where('(position = 0 OR position IS NULL OR position > 0)');

            $categoryProducts = $connection->fetchAll($select);
            $updatedCount = 0;
            foreach ($categoryProducts as $categoryProduct) {
                $categoryId = $categoryProduct['category_id'];
                $currentPosition = $categoryProduct['position'];

                if ($currentPosition === null || $currentPosition == 0 || $currentPosition > 0) {
                    $maxSelect = $connection->select()
                        ->from($tableName, ['MAX(position) as max_position'])
                        ->where('category_id = ?', $categoryId)
                        ->where('product_id != ?', $productId)
                        ->where('position IS NOT NULL');

                    $maxPosition = $connection->fetchOne($maxSelect);
                    $nextPosition = $maxPosition ? (int)$maxPosition + 1 : 1;

                    $connection->update(
                        $tableName,
                        ['position' => $nextPosition],
                        [
                            'category_id = ?' => $categoryId,
                            'product_id = ?' => $productId
                        ]
                    );
                    $updatedCount++;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('[ProductSaveAfter::execute] Exception', [
                'message' => $e->getMessage(),
                'product_id' => $productId,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
