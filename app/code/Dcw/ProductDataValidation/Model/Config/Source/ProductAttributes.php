<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Product attribute options for Admin multiselect configuration.
 */
class ProductAttributes implements OptionSourceInterface
{
    public function __construct(
        private readonly AttributeCollectionFactory $attributeCollectionFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $options = [];
        $collection = $this->attributeCollectionFactory->create();
        $collection->addVisibleFilter();
        $collection->setOrder('frontend_label', 'ASC');

        foreach ($collection as $attribute) {
            $code = (string) $attribute->getAttributeCode();
            if ($code === '') {
                continue;
            }
            $label = (string) $attribute->getFrontendLabel();
            if ($label === '') {
                $label = $code;
            }
            $options[] = [
                'value' => $code,
                'label' => sprintf('%s (%s)', $label, $code),
            ];
        }

        // Ensure media/image roles appear even when not in standard visible filter edge cases.
        $extra = [
            'image' => 'Base Image (image)',
            'small_image' => 'Small Image (small_image)',
            'thumbnail' => 'Thumbnail (thumbnail)',
        ];
        $existing = array_column($options, 'value');
        foreach ($extra as $code => $label) {
            if (!in_array($code, $existing, true)) {
                $options[] = ['value' => $code, 'label' => $label];
            }
        }

        usort(
            $options,
            static fn(array $a, array $b): int => strcasecmp((string) $a['label'], (string) $b['label'])
        );

        return $options;
    }
}
