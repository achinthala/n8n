<?php
declare(strict_types=1);

namespace Dcw\AiContentCreator\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Reads AI Content Creator system configuration.
 */
class Config
{
    public const XML_PATH_ENABLED = 'dcw_ai_content_creator/general/enabled';
    public const XML_PATH_PROVIDER = 'dcw_ai_content_creator/general/provider';
    public const XML_PATH_TIMEOUT = 'dcw_ai_content_creator/general/timeout';

    public const XML_PATH_OPENAI_API_KEY = 'dcw_ai_content_creator/openai/api_key';
    public const XML_PATH_OPENAI_MODEL = 'dcw_ai_content_creator/openai/model';

    public const XML_PATH_GEMINI_API_KEY = 'dcw_ai_content_creator/gemini/api_key';
    public const XML_PATH_GEMINI_MODEL = 'dcw_ai_content_creator/gemini/model';

    public const XML_PATH_BRAND_VOICE = 'dcw_ai_content_creator/prompts/brand_voice';
    public const XML_PATH_PROMPT_PREFIX = 'dcw_ai_content_creator/prompts/';
    public const XML_PATH_ATTRIBUTE_PREFIX = 'dcw_ai_content_creator/attributes/';

    /**
     * MVP hard allow-list of Magento-owned native product attributes.
     */
    public const ALLOWED_ATTRIBUTES = [
        'description',
        'short_description',
        'meta_title',
        'meta_description',
    ];

    public const PROVIDER_OPENAI = 'openai';
    public const PROVIDER_GEMINI = 'gemini';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getDefaultProvider(?int $storeId = null): string
    {
        $provider = (string) $this->scopeConfig->getValue(
            self::XML_PATH_PROVIDER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $provider !== '' ? $provider : self::PROVIDER_OPENAI;
    }

    public function getTimeoutSeconds(?int $storeId = null): int
    {
        $timeout = (int) $this->scopeConfig->getValue(
            self::XML_PATH_TIMEOUT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $timeout > 0 ? $timeout : 60;
    }

    public function getOpenAiApiKey(?int $storeId = null): string
    {
        return $this->getDecryptedValue(self::XML_PATH_OPENAI_API_KEY, $storeId);
    }

    public function getOpenAiModel(?int $storeId = null): string
    {
        $model = trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_OPENAI_MODEL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return $model !== '' ? $model : 'gpt-4o-mini';
    }

    public function getGeminiApiKey(?int $storeId = null): string
    {
        return $this->getDecryptedValue(self::XML_PATH_GEMINI_API_KEY, $storeId);
    }

    public function getGeminiModel(?int $storeId = null): string
    {
        $model = trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_GEMINI_MODEL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return $model !== '' ? $model : 'gemini-1.5-flash';
    }

    public function getBrandVoice(?int $storeId = null): string
    {
        return trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_BRAND_VOICE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    public function getFieldPrompt(string $attributeCode, ?int $storeId = null): string
    {
        if (!in_array($attributeCode, self::ALLOWED_ATTRIBUTES, true)) {
            return '';
        }

        return trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_PROMPT_PREFIX . $attributeCode,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Returns allow-listed attribute codes that are enabled in config.
     *
     * @return string[]
     */
    public function getEnabledAttributes(?int $storeId = null): array
    {
        $enabled = [];
        foreach (self::ALLOWED_ATTRIBUTES as $code) {
            if ($this->isAttributeEnabled($code, $storeId)) {
                $enabled[] = $code;
            }
        }

        return $enabled;
    }

    public function isAttributeEnabled(string $attributeCode, ?int $storeId = null): bool
    {
        if (!in_array($attributeCode, self::ALLOWED_ATTRIBUTES, true)) {
            return false;
        }

        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ATTRIBUTE_PREFIX . $attributeCode,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isAttributeAllowed(string $attributeCode, ?int $storeId = null): bool
    {
        return $this->isAttributeEnabled($attributeCode, $storeId);
    }

    private function getDecryptedValue(string $path, ?int $storeId = null): string
    {
        $value = trim((string) $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        if ($value === '') {
            return '';
        }

        // Obscure + Encrypted may return Magento ciphertext when metadata decrypt is skipped.
        if (preg_match('/^\d+:\d+:/', $value)) {
            return trim((string) $this->encryptor->decrypt($value));
        }

        return $value;
    }
}
