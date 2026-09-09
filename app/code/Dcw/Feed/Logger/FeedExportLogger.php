<?php

declare(strict_types=1);

namespace Dcw\Feed\Logger;

class FeedExportLogger
{
    private const LOG_FILE = '/var/log/dcw_feed_export.log';

    public function logExportDebug(
        string $sku,
        ?float $originalPrice,
        ?float $calculatedPrice,
        ?float $feedRowPrice,
        ?float $feedRowSalePrice,
        ?float $feedRowPriceAfter,
        ?float $feedRowSalePriceAfter
    ): void {
        $this->createLogs(
            sprintf(
                'SKU: %s | Original Price: %s | Calculated Price: %s | '
                . 'Feed Row Price Before Export: %s | Feed Row Sale Price Before Export: %s | '
                . 'Feed Row Price After Update: %s | Feed Row Sale Price After Update: %s',
                $sku,
                $this->formatPrice($originalPrice),
                $this->formatPrice($calculatedPrice),
                $this->formatPrice($feedRowPrice),
                $this->formatPrice($feedRowSalePrice),
                $this->formatPrice($feedRowPriceAfter),
                $this->formatPrice($feedRowSalePriceAfter)
            )
        );
    }

    public function createLogs(string $message): void
    {
        $writer = new \Zend_Log_Writer_Stream(BP . self::LOG_FILE);
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

    private function formatPrice(?float $price): string
    {
        return $price === null ? 'n/a' : (string) $price;
    }
}
