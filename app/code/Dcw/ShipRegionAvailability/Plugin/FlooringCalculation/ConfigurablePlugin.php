<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Plugin\FlooringCalculation;

use Dcw\ShipRegionAvailability\Model\Config;
use Dcw\ShipRegionAvailability\Model\RegionCodeNormalizer;
use Dcw\ShipRegionAvailability\ViewModel\Product\ShipRegionAvailability;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ProductConfigurable;
use Magento\Framework\Serialize\SerializerInterface;

class ConfigurablePlugin
{
    public function __construct(
        private readonly Config $moduleConfig,
        private readonly SerializerInterface $serializer,
        private readonly RegionCodeNormalizer $regionCodeNormalizer,
        private readonly ShipRegionAvailability $shipRegionAvailability
    ) {
    }

    /**
     * @param ProductConfigurable $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGetAllowProducts(ProductConfigurable $subject, $result)
    {
        if (!$this->moduleConfig->isEnabled((int) $subject->getProduct()->getStoreId())) {
            return $result;
        }

        if (!$this->shipRegionAvailability->isEnabledForProduct($subject->getProduct())) {
            return $result;
        }

        if ($result instanceof \Magento\Catalog\Model\ResourceModel\Product\Collection) {
            $result->addAttributeToSelect([Config::ATTR_SHIPS_FROM_REGION, 'sku']);
        }

        return $result;
    }

    public function afterGetJsonConfig(ProductConfigurable $subject, string $result): string
    {
        $storeId = (int) $subject->getProduct()->getStoreId();
        if (!$this->moduleConfig->isEnabled($storeId)) {
            return $result;
        }

        if (!$this->shipRegionAvailability->isEnabledForProduct($subject->getProduct())) {
            return $result;
        }

        try {
            $config = $this->serializer->unserialize($result);
        } catch (\InvalidArgumentException) {
            return $result;
        }

        if (!is_array($config)) {
            return $result;
        }

        $shipsFromRegion = [];
        $childSkus = [];
        foreach ($subject->getAllowProducts() as $product) {
            $productId = (int) $product->getId();
            $childSkus[$productId] = (string) $product->getSku();
            $shipsFromRegion[$productId] = $this->regionCodeNormalizer->resolveForProduct($product);
        }

        $config['ships_from_region'] = $shipsFromRegion;
        $config['ships_from_region_skus'] = $childSkus;
        $config['ship_region_enabled'] = (int) $subject->getProduct()->getData(
            Config::ATTR_SHIP_REGION_AVAILABILITY
        ) === 1;

        try {
            return $this->serializer->serialize($config);
        } catch (\InvalidArgumentException) {
            return $result;
        }
    }

}
