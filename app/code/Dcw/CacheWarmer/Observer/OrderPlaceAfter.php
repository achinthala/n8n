<?php

declare(strict_types=1);

namespace Dcw\CacheWarmer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Dcw\CacheWarmer\Service\CacheWarmerService;
use Psr\Log\LoggerInterface;

/**
 * Observer for sales_order_place_after event
 * 
 * Warms cache for configurable products and their categories when an order is placed
 */
class OrderPlaceAfter implements ObserverInterface
{
    /**
     * XML path for enabled configuration
     */
    const XML_PATH_ENABLED = 'cachewarmer/general/enabled';

    /**
     * @var CacheWarmerService
     */
    private $cacheWarmerService;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CacheWarmerService $cacheWarmerService
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        CacheWarmerService $cacheWarmerService,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->cacheWarmerService = $cacheWarmerService;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        // Check if cache warmer is enabled via admin configuration
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return;
        }

        try {
            /** @var OrderInterface $order */
            $order = $observer->getEvent()->getOrder();
            
            if (!$order) {
                return;
            }

            // Note: $order->getId() may be null if order hasn't been saved yet,
            // but we can still process order items from the order object
            $this->cacheWarmerService->warmCacheForOrder($order);
        } catch (\Exception $e) {
            $this->logger->error(
                'CacheWarmer: Error during cache warming',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );
        }
    }
}
