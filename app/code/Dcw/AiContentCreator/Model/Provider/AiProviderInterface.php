<?php
declare(strict_types=1);

namespace Dcw\AiContentCreator\Model\Provider;

/**
 * Contract for outbound AI text generation providers.
 */
interface AiProviderInterface
{
    /**
     * Provider code (openai|gemini).
     */
    public function getCode(): string;

    /**
     * Generate text content from a prompt.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generate(string $prompt, ?int $storeId = null): string;
}
