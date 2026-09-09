<?php

declare(strict_types=1);

namespace Dcw\ShoppingCart\Model;

/**
 * File logger for custom_price diagnostics (same pattern as OrderPlacedAfterObserver::createLog).
 */
class CustomPriceDebugLogger
{
    public function log(string $message): void
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom_price_debug.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }
}
