<?php
declare(strict_types=1);

namespace Dcw\SalesOrderGridTotal\Model\DataProvider\Plugin;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Psr\Log\LoggerInterface;

/**
 * Plugin to add total amount to sales order grid data (used by frontend)
 */
class GridTotal
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Add grid_total to the data array so frontend can display it
     *
     * @param DataProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetData(
        DataProvider $subject,
        array $result
    ): array {
        if ($subject->getName() !== 'sales_order_grid_data_source') {
            return $result;
        }

        try {
            $totalAmount = 0;
            $totalCount = 0;
            $items = $result['items'] ?? [];

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $val = $item['base_grand_total'] ?? $item['grand_total'] ?? null;
                if ($val !== null && $val !== '') {
                    $totalAmount += (float) $val;
                    $totalCount++;
                }
            }

            $result['grid_total'] = [
                'total_amount' => $totalAmount,
                'base_total_amount' => $totalAmount,
                'total_count' => $totalCount,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('SalesOrderGridTotal: ' . $e->getMessage());
        }

        return $result;
    }
}
