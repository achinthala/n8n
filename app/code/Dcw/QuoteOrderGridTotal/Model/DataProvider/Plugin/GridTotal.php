<?php
declare(strict_types=1);

namespace Dcw\QuoteOrderGridTotal\Model\DataProvider\Plugin;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Psr\Log\LoggerInterface;

/**
 * Adds a lightweight total to the Amasty quote grid data payload (fallback).
 * The main total is calculated server-side via the GridTotal controller.
 */
class GridTotal
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function afterGetData(DataProvider $subject, array $result): array
    {
        // Expected data source name for Amasty quote listing.
        if ($subject->getName() !== 'amasty_quote_grid_data_source') {
            return $result;
        }

        try {
            $totalAmount = 0.0;
            $totalCount = 0;

            $items = $result['items'] ?? [];
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $val = $item['base_grand_total'] ?? $item['grand_total'] ?? null;
                if ($val !== null && $val !== '') {
                    $totalAmount += (float)$val;
                    $totalCount++;
                }
            }

            $result['grid_total'] = [
                'total_amount' => $totalAmount,
                'base_total_amount' => $totalAmount,
                'total_count' => $totalCount,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('QuoteOrderGridTotal: ' . $e->getMessage(), ['exception' => $e]);
        }

        return $result;
    }
}

