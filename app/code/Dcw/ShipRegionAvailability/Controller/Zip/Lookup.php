<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Controller\Zip;

use Dcw\ShipRegionAvailability\Api\RegionLookupInterface;
use Dcw\ShipRegionAvailability\Model\Config;
use Dcw\ShipRegionAvailability\Model\RegionCodeNormalizer;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Internal storefront AJAX: resolve ZIP to region (no public API module).
 */
class Lookup extends Action implements HttpGetActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly RegionLookupInterface $regionLookup,
        private readonly Config $moduleConfig,
        private readonly RegionCodeNormalizer $regionCodeNormalizer
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        if (!$this->moduleConfig->isEnabled()) {
            return $this->resultJsonFactory->create()->setData([
                'success' => false,
                'message' => __('Ship region availability is currently disabled.'),
            ]);
        }

        $zip = (string) $this->getRequest()->getParam('zip', '');
        $normalized = $this->regionLookup->normalizeZipCode($zip);

        if ($normalized === null) {
            return $this->resultJsonFactory->create()->setData([
                'success' => false,
                'message' => __('Please enter a valid 5-digit ZIP code.'),
            ]);
        }

        $region = $this->regionLookup->getRegionByZip($normalized);
        if ($region === null || !$region->getRegionId()) {
            return $this->resultJsonFactory->create()->setData([
                'success' => false,
                'message' => __('We could not find shipping regions for this ZIP code %1.', $normalized),
                'zip_code' => $normalized,
            ]);
        }

        $regionCode = $this->regionCodeNormalizer->normalizeZipRegion($region)
            ?? strtolower(trim((string) $region->getCode()));

        return $this->resultJsonFactory->create()->setData([
            'success' => true,
            'zip_code' => $normalized,
            'region_id' => $region->getRegionId(),
            'region_code' => $regionCode,
            'region_name' => $region->getName(),
        ]);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
