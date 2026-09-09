<?php
declare(strict_types=1);

namespace Dcw\AiContentCreator\Ui\DataProvider\Product\Form\Modifier;

use Dcw\AiContentCreator\Model\Config;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Form\Fieldset;

/**
 * Adds AI Content Creator fieldset/button to the Admin product form.
 */
class AiContent extends AbstractModifier
{
    public const GROUP_AI_CONTENT = 'dcw_ai_content_creator';
    public const GROUP_AI_CONTENT_SORT = 22;

    public function __construct(
        private readonly LocatorInterface $locator,
        private readonly Config $config,
        private readonly AuthorizationInterface $authorization,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data): array
    {
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta): array
    {
        if (!$this->config->isEnabled()
            || !$this->authorization->isAllowed('Dcw_AiContentCreator::generate')
        ) {
            return $meta;
        }

        $product = $this->locator->getProduct();
        if (!$product || !(int) $product->getId()) {
            // New unsaved products: generation needs an existing product ID for context load.
            return $meta;
        }

        $enabledAttributes = $this->config->getEnabledAttributes();
        if ($enabledAttributes === []) {
            return $meta;
        }

        $attributeOptions = [];
        foreach ($enabledAttributes as $code) {
            $attributeOptions[] = [
                'value' => $code,
                'label' => $this->getAttributeLabel($code),
            ];
        }

        $meta[self::GROUP_AI_CONTENT] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('AI Content Creator'),
                        'collapsible' => true,
                        'opened' => false,
                        'componentType' => Fieldset::NAME,
                        'sortOrder' => self::GROUP_AI_CONTENT_SORT,
                    ],
                ],
            ],
            'children' => [
                'dcw_ai_content_panel' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => 'container',
                                'component' => 'Dcw_AiContentCreator/js/form/element/ai-content',
                                'template' => 'Dcw_AiContentCreator/form/element/ai-content',
                                'sortOrder' => 10,
                                'formElement' => 'container',
                                'label' => __('AI Content Creator'),
                                'generateUrl' => $this->urlBuilder->getUrl('dcw_ai_content/generate/index'),
                                'productId' => (int) $product->getId(),
                                'storeId' => (int) $this->locator->getStore()->getId(),
                                'defaultProvider' => $this->config->getDefaultProvider(),
                                'attributeOptions' => $attributeOptions,
                                'providerOptions' => [
                                    ['value' => Config::PROVIDER_OPENAI, 'label' => (string) __('OpenAI')],
                                    ['value' => Config::PROVIDER_GEMINI, 'label' => (string) __('Google Gemini')],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $meta;
    }

    private function getAttributeLabel(string $code): string
    {
        $labels = [
            'description' => (string) __('Description'),
            'short_description' => (string) __('Short Description'),
            'meta_title' => (string) __('Meta Title'),
            'meta_description' => (string) __('Meta Description'),
        ];

        return $labels[$code] ?? $code;
    }
}
