<?php
declare(strict_types=1);

namespace Dcw\PurchaseOrderReview\Model;

use Dcw\PurchaseOrderReview\Helper\Config;
use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class ZendeskTicket
{
    public function __construct(
        private readonly ClientFactory $httpClientFactory,
        private readonly Json $json,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array{success: bool, status: int|null, body: array|string|null, error: string|null}
     */
    public function create(
        int $storeId,
        string $subject,
        string $description,
        string $requesterEmail,
        string $requesterName = ''
    ): array {
        if (!$this->config->isZendeskEnabled($storeId)) {
            return ['success' => false, 'status' => null, 'body' => null, 'error' => 'Zendesk disabled'];
        }

        $subdomain = $this->normalizeZendeskSubdomain($this->config->getZendeskSubdomain($storeId));
        $email = trim($this->config->getZendeskApiEmail($storeId));
        $token = trim((string) $this->config->getZendeskApiToken($storeId));

        if ($subdomain === '' || $email === '' || $token === '') {
            return ['success' => false, 'status' => null, 'body' => null, 'error' => 'Zendesk credentials incomplete'];
        }

        $url = sprintf('https://%s.zendesk.com/api/v2/tickets.json', $subdomain);
        $requester = ['email' => $requesterEmail];
        $name = trim($requesterName);
        if ($name !== '') {
            $requester['name'] = $name;
        }
        $payload = [
            'ticket' => [
                'subject' => $subject,
                'comment' => ['body' => $description],
                'requester' => $requester,
                'priority' => 'normal',
            ],
        ];

        try {
            $jsonBody = $this->json->serialize($payload);
            $this->logger->info('Zendesk ticket API request', ['url' => $url]);

            $curl = $this->httpClientFactory->create();
            $curl->addHeader('Content-Type', 'application/json');
            $curl->addHeader('Accept', 'application/json');
            $curl->setCredentials($email . '/token', $token);
            $curl->post($url, $jsonBody);

            $status = (int) $curl->getStatus();
            $raw = $curl->getBody();
            $this->logger->info('Zendesk ticket API response', ['status' => $status]);

            $decoded = is_string($raw) ? $this->json->unserialize($raw) : null;

            if ($status >= 200 && $status < 300) {
                return ['success' => true, 'status' => $status, 'body' => is_array($decoded) ? $decoded : null, 'error' => null];
            }

            $this->logger->warning('Zendesk ticket API returned non-success', [
                'status' => $status,
                'response' => $raw,
            ]);

            return ['success' => false, 'status' => $status, 'body' => $decoded, 'error' => 'HTTP ' . $status];
        } catch (\Throwable $e) {
            $this->logger->error('Zendesk ticket exception: ' . $e->getMessage());
            return ['success' => false, 'status' => null, 'body' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Accept "mycompany" or pasted host like "mycompany.zendesk.com".
     */
    private function normalizeZendeskSubdomain(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $value)) {
            $host = parse_url($value, PHP_URL_HOST);
            $value = is_string($host) && $host !== '' ? $host : $value;
        }
        $value = preg_replace('#^/+|/+$#', '', $value);
        if (str_ends_with(strtolower($value), '.zendesk.com')) {
            $value = substr($value, 0, -strlen('.zendesk.com'));
        }

        return trim($value, '/');
    }
}
