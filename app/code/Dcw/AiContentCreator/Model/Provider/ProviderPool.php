<?php
declare(strict_types=1);

namespace Dcw\AiContentCreator\Model\Provider;

use Magento\Framework\Exception\LocalizedException;

/**
 * Resolves the configured AI provider implementation.
 */
class ProviderPool
{
    /**
     * @param AiProviderInterface[] $providers
     */
    public function __construct(
        private readonly array $providers = []
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function get(string $code): AiProviderInterface
    {
        if (!isset($this->providers[$code])) {
            throw new LocalizedException(__('Unknown AI provider: %1', $code));
        }

        $provider = $this->providers[$code];
        if (!$provider instanceof AiProviderInterface) {
            throw new LocalizedException(__('Invalid AI provider configuration for: %1', $code));
        }

        return $provider;
    }

    /**
     * @return string[]
     */
    public function getCodes(): array
    {
        return array_keys($this->providers);
    }
}
