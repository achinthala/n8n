<?php
declare(strict_types=1);

namespace Dcw\AiContentCreator\Model\Service;

use Dcw\AiContentCreator\Logger\Logger;
use Dcw\AiContentCreator\Model\Config;
use Dcw\AiContentCreator\Model\Prompt\Builder as PromptBuilder;
use Dcw\AiContentCreator\Model\Provider\ProviderPool;
use Dcw\AiContentCreator\Model\Sanitizer\HtmlSanitizer;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Orchestrates AI content generation for allow-listed product attributes.
 */
class ContentGenerator
{
    public function __construct(
        private readonly Config $config,
        private readonly ProviderPool $providerPool,
        private readonly PromptBuilder $promptBuilder,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly HtmlSanitizer $htmlSanitizer,
        private readonly Logger $logger
    ) {
    }

    /**
     * Generate draft content. Does NOT persist to the product.
     *
     * @param array<string, string> $currentValues
     * @return array{content: string, provider: string, attribute: string, mode: string}
     * @throws LocalizedException
     */
    public function generateDraft(
        int $productId,
        string $attributeCode,
        string $mode = PromptBuilder::MODE_GENERATE,
        ?string $providerCode = null,
        array $currentValues = [],
        string $extraInstructions = '',
        ?int $storeId = null
    ): array {
        if (!$this->config->isEnabled($storeId)) {
            throw new LocalizedException(__('AI Content Creator is disabled.'));
        }

        if (!$this->config->isAttributeAllowed($attributeCode, $storeId)) {
            throw new LocalizedException(
                __('Attribute "%1" is not allow-listed for AI generation.', $attributeCode)
            );
        }

        $product = $this->productRepository->getById($productId, false, $storeId);
        $providerCode = $providerCode !== null && $providerCode !== ''
            ? $providerCode
            : $this->config->getDefaultProvider($storeId);

        $provider = $this->providerPool->get($providerCode);
        $prompt = $this->promptBuilder->build(
            $product,
            $attributeCode,
            $mode,
            $currentValues,
            $extraInstructions,
            $storeId
        );

        $this->logger->info('AI generate request', [
            'product_id' => $productId,
            'sku' => $product->getSku(),
            'attribute' => $attributeCode,
            'mode' => $mode,
            'provider' => $providerCode,
            'store_id' => $storeId,
        ]);

        try {
            $raw = $provider->generate($prompt, $storeId);
            $content = $this->htmlSanitizer->sanitize($raw, $attributeCode);

            $this->logger->info('AI generate success', [
                'product_id' => $productId,
                'attribute' => $attributeCode,
                'provider' => $providerCode,
                'content_length' => strlen($content),
            ]);

            return [
                'content' => $content,
                'provider' => $providerCode,
                'attribute' => $attributeCode,
                'mode' => $mode,
            ];
        } catch (LocalizedException $e) {
            $this->logger->error('AI generate failed', [
                'product_id' => $productId,
                'attribute' => $attributeCode,
                'provider' => $providerCode,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
