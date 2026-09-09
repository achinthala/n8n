<?php
declare(strict_types=1);

namespace Dcw\AiContentCreator\Model\Provider;

use Dcw\AiContentCreator\Logger\Logger;
use Dcw\AiContentCreator\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * OpenAI Chat Completions provider.
 */
class OpenAiProvider implements AiProviderInterface
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';

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
        return Config::PROVIDER_OPENAI;
    }

    /**
     * @inheritdoc
     */
    public function generate(string $prompt, ?int $storeId = null): string
    {
        $apiKey = $this->config->getOpenAiApiKey($storeId);
        if ($apiKey === '') {
            throw new LocalizedException(__('OpenAI API key is missing. Configure it under Stores → Configuration → DCW → AI Content Creator.'));
        }

        $model = $this->config->getOpenAiModel($storeId);
        $timeout = $this->config->getTimeoutSeconds($storeId);

        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a Magento product content assistant. Return only the requested content with no markdown fences unless HTML is requested.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.7,
        ];

        try {
            $client = $this->httpClientFactory->create();
            $client->setTimeout($timeout);
            $client->addHeader('Content-Type', 'application/json');
            $client->addHeader('Authorization', 'Bearer ' . $apiKey);
            $client->post(self::API_URL, $this->json->serialize($payload));

            $status = (int) $client->getStatus();
            $raw = (string) $client->getBody();

            if ($status < 200 || $status >= 300) {
                $this->logger->warning('OpenAI API non-success', [
                    'status' => $status,
                    'body_excerpt' => mb_substr($raw, 0, 500),
                ]);
                throw new LocalizedException(
                    __('OpenAI request failed (HTTP %1). Check API key, model, and Cloud egress.', $status)
                );
            }

            $decoded = $this->json->unserialize($raw);
            $content = $decoded['choices'][0]['message']['content'] ?? null;
            if (!is_string($content) || trim($content) === '') {
                throw new LocalizedException(__('OpenAI returned an empty or malformed response.'));
            }

            return $this->stripMarkdownFences(trim($content));
        } catch (LocalizedException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('OpenAI exception: ' . $e->getMessage());
            throw new LocalizedException(
                __('OpenAI request failed: %1', $e->getMessage())
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
