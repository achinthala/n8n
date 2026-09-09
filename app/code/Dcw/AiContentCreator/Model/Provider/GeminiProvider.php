<?php
declare(strict_types=1);

namespace Dcw\AiContentCreator\Model\Provider;

use Dcw\AiContentCreator\Logger\Logger;
use Dcw\AiContentCreator\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Google Gemini generateContent provider.
 */
class GeminiProvider implements AiProviderInterface
{
    private const API_URL_TEMPLATE = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s';

    public function __construct(
        private readonly Config $config,
        private readonly ClientFactory $httpClientFactory,
        private readonly Json $json,
        private readonly Logger $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getCode(): string
    {
        return Config::PROVIDER_GEMINI;
    }

    /**
     * @inheritdoc
     */
    public function generate(string $prompt, ?int $storeId = null): string
    {
        $apiKey = $this->config->getGeminiApiKey($storeId);
        if ($apiKey === '') {
            throw new LocalizedException(__('Gemini API key is missing. Configure it under Stores → Configuration → DCW → AI Content Creator.'));
        }

        $model = $this->config->getGeminiModel($storeId);
        $timeout = $this->config->getTimeoutSeconds($storeId);
        $url = sprintf(self::API_URL_TEMPLATE, rawurlencode($model), rawurlencode($apiKey));

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
            ],
        ];

        try {
            $client = $this->httpClientFactory->create();
            $client->setTimeout($timeout);
            $client->addHeader('Content-Type', 'application/json');
            $client->post($url, $this->json->serialize($payload));

            $status = (int) $client->getStatus();
            $raw = (string) $client->getBody();

            if ($status < 200 || $status >= 300) {
                $this->logger->warning('Gemini API non-success', [
                    'status' => $status,
                    'body_excerpt' => mb_substr($raw, 0, 500),
                ]);
                throw new LocalizedException(
                    __('Gemini request failed (HTTP %1). Check API key, model, and Cloud egress.', $status)
                );
            }

            $decoded = $this->json->unserialize($raw);
            $content = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if (!is_string($content) || trim($content) === '') {
                throw new LocalizedException(__('Gemini returned an empty or malformed response.'));
            }

            return $this->stripMarkdownFences(trim($content));
        } catch (LocalizedException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Gemini exception: ' . $e->getMessage());
            throw new LocalizedException(
                __('Gemini request failed: %1', $e->getMessage())
            );
        }
    }

    private function stripMarkdownFences(string $content): string
    {
        if (preg_match('/^```(?:html|markdown|text)?\s*(.*?)\s*```$/is', $content, $matches)) {
            return trim($matches[1]);
        }

        return $content;
    }
}
