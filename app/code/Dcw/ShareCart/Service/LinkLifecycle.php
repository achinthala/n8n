<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Service;

use Dcw\ShareCart\Api\Data\ShareCartInterface;
use Dcw\ShareCart\Model\Config;
use Dcw\ShareCart\Model\ResourceModel\ShareCart\CollectionFactory;
use Dcw\ShareCart\Model\ShareCart;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

class LinkLifecycle
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly Config $config,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    public function isExpired(ShareCart $shareCart): bool
    {
        if ($shareCart->getLinkStatus() === ShareCartInterface::LINK_STATUS_EXPIRED) {
            return true;
        }

        $expiresAt = (string) $shareCart->getData('expires_at');

        return $expiresAt !== '' && strtotime($expiresAt) < (int) $this->dateTime->gmtTimestamp();
    }

    public function markExpiredIfNeeded(ShareCart $shareCart): ShareCart
    {
        if ($this->isExpired($shareCart) && $shareCart->getLinkStatus() === ShareCartInterface::LINK_STATUS_ACTIVE) {
            $shareCart->setLinkStatus(ShareCartInterface::LINK_STATUS_EXPIRED);
            $shareCart->save();
        }

        return $shareCart;
    }

    public function markUsed(ShareCart $shareCart): void
    {
        $shareCart->setLinkStatus(ShareCartInterface::LINK_STATUS_USED);
        $shareCart->setData('used_at', $this->dateTime->gmtDate());
        $shareCart->save();
    }

    public function canBeLoaded(ShareCart $shareCart): bool
    {
        $this->markExpiredIfNeeded($shareCart);

        return $shareCart->getLinkStatus() === ShareCartInterface::LINK_STATUS_ACTIVE;
    }

    public function expireOutdatedLinks(): int
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('link_status', ShareCartInterface::LINK_STATUS_ACTIVE);
        $collection->addFieldToFilter('expires_at', ['lt' => $this->dateTime->gmtDate()]);

        $count = 0;
        foreach ($collection as $shareCart) {
            $this->markExpiredIfNeeded($shareCart);
            $count++;
        }

        if ($count > 0) {
            $this->logger->info('ShareCart links expired', ['count' => $count]);
        }

        return $count;
    }

    public function calculateExpiresAt(?int $storeId = null): string
    {
        $days = max(1, $this->config->getLinkExpiryDays($storeId));
        $timestamp = (int) $this->dateTime->gmtTimestamp() + ($days * 86400);

        return gmdate('Y-m-d H:i:s', $timestamp);
    }
}
