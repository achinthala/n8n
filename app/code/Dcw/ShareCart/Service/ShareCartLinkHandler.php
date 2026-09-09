<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Service;

use Dcw\ShareCart\Model\Config;
use Dcw\ShareCart\Model\ResourceModel\ShareCart\CollectionFactory;
use Dcw\ShareCart\Model\ShareCart;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class ShareCartLinkHandler
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly LinkLifecycle $linkLifecycle,
        private readonly CartRestorer $cartRestorer,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function processByToken(string $token): ShareCart
    {
        $token = trim($token);

        if (!$this->config->isEnabled()) {
            throw new LocalizedException(__('This shared cart link is no longer available.'));
        }

        if ($token === '') {
            throw new LocalizedException(__('Invalid shared cart link.'));
        }

        $shareCart = $this->loadShareCartByToken($token);

        if (!$shareCart->getId()) {
            throw new LocalizedException(__('Shared cart link not found.'));
        }

        if (!$this->linkLifecycle->canBeLoaded($shareCart)) {
            throw new LocalizedException(__('This shared cart link has expired or has already been used.'));
        }

        $this->cartRestorer->restore($shareCart);
        $this->linkLifecycle->markUsed($shareCart);

        $this->logger->info('ShareCart restored', [
            'share_id' => $shareCart->getShareId(),
        ]);

        return $shareCart;
    }

    private function loadShareCartByToken(string $token): ShareCart
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('token', $token);

        /** @var ShareCart $shareCart */
        $shareCart = $collection->getFirstItem();

        return $shareCart;
    }
}
