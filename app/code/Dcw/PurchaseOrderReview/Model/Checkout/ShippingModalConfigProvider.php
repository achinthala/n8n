<?php
declare(strict_types=1);

namespace Dcw\PurchaseOrderReview\Model\Checkout;

use Dcw\PurchaseOrderReview\Helper\Config;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Cms\Api\GetBlockByIdentifierInterface;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Exposes CMS block content and title for the PO payment shipping modal on checkout.
 */
class ShippingModalConfigProvider implements ConfigProviderInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly GetBlockByIdentifierInterface $getBlockByIdentifier,
        private readonly FilterProvider $filterProvider,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function getConfig(): array
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        if (!$this->config->isModuleEnabled($storeId)) {
            return [];
        }

        $identifier = $this->config->getShippingModalCmsBlockIdentifier($storeId);
        $html = '';
        $title = '';

        try {
            $block = $this->getBlockByIdentifier->execute($identifier, $storeId);
            if ($block->isActive()) {
                $title = trim((string) $block->getTitle());
                $html = $this->filterProvider->getBlockFilter()
                    ->setStoreId($storeId)
                    ->filter((string) $block->getContent());
            }
        } catch (NoSuchEntityException) {
            // Leave html/title empty; JS may use fallbacks.
        }

        return [
            'dcwPoShippingModalHtml' => $html,
            'dcwPoShippingModalTitle' => $title,
        ];
    }
}
