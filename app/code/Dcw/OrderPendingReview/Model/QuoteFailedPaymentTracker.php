<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Psr\Log\LoggerInterface;

class QuoteFailedPaymentTracker
{
    public const LOG_PREFIX = '[OrderPendingReview][dcw_failed_payment_attempts]';

    private const REGISTRY_ONCE_PREFIX = 'dcw_fpa_once_';

    private static bool $shutdownHandlerRegistered = false;

    /** @var array<int, true> */
    private static array $pendingQuoteEntityIds = [];

    /** @var array<int, array<string, mixed>> */
    private static array $pendingContext = [];

    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly Registry $registry,
        private readonly MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Address validation and auth lockouts are not payment declines and must not set fraud flag 2.
     */
    public function isNonPaymentFailure(\Throwable $e): bool
    {
        for ($current = $e; $current instanceof \Throwable; $current = $current->getPrevious()) {
            if ($current instanceof AuthorizationException) {
                return true;
            }
            foreach ($this->exceptionTexts($current) as $text) {
                if ($this->isAddressValidationText($text)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Increment failed place-order attempts for the given cart (masked or numeric id).
     * Uses request-level deduplication plus a deferred atomic UPDATE at PHP shutdown so later
     * quote saves in the same request (payment failure handling) cannot overwrite this column.
     *
     * @param array<string, mixed> $context Optional payment_method / exception / message for the log line
     */
    public function increment(string|int $cartId, array $context = []): void
    {
        try {
            $quoteEntityId = $this->resolveQuoteEntityId($cartId);
            if ($quoteEntityId <= 0) {
                $this->logger->warning(self::LOG_PREFIX . ' could not resolve quote entity id', [
                    'cart_id' => (string) $cartId,
                ]);
                return;
            }
            $this->mergePendingContext($quoteEntityId, $context);
            $onceKey = self::REGISTRY_ONCE_PREFIX . $quoteEntityId;
            if ($this->registry->registry($onceKey)) {
                return;
            }
            $this->registry->register($onceKey, true);

            self::$pendingQuoteEntityIds[$quoteEntityId] = true;
            if (!self::$shutdownHandlerRegistered) {
                self::$shutdownHandlerRegistered = true;
                register_shutdown_function(function (): void {
                    $this->flushPendingIncrements();
                });
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->warning(self::LOG_PREFIX . ' quote or masked id not found', [
                'cart_id' => (string) $cartId,
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error(self::LOG_PREFIX . ' unexpected error', [
                'cart_id' => (string) $cartId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Runs after the rest of the request so quote saves triggered during exception handling
     * do not overwrite dcw_failed_payment_attempts before we persist it.
     */
    private function flushPendingIncrements(): void
    {
        self::$shutdownHandlerRegistered = false;
        $quoteEntityIds = array_keys(self::$pendingQuoteEntityIds);
        self::$pendingQuoteEntityIds = [];

        if ($quoteEntityIds === []) {
            return;
        }

        $table = $this->resourceConnection->getTableName('quote');
        $connection = $this->resourceConnection->getConnection();

        foreach ($quoteEntityIds as $qid) {
            try {
                $connection->update(
                    $table,
                    ['dcw_failed_payment_attempts' => new Expression('IFNULL(dcw_failed_payment_attempts, 0) + 1')],
                    ['entity_id = ?' => $qid]
                );
                $newVal = (int) $connection->fetchOne(
                    $connection->select()
                        ->from($table, ['dcw_failed_payment_attempts'])
                        ->where('entity_id = ?', $qid)
                );
                $payload = [
                    'quote_entity_id' => $qid,
                    'dcw_failed_payment_attempts' => $newVal,
                ];
                foreach (['payment_method', 'exception', 'message', 'source'] as $key) {
                    if (isset(self::$pendingContext[$qid][$key]) && self::$pendingContext[$qid][$key] !== '') {
                        $payload[$key] = self::$pendingContext[$qid][$key];
                    }
                }
                unset(self::$pendingContext[$qid]);
                $this->logger->info(self::LOG_PREFIX . ' payment failure', $payload);
            } catch (\Throwable $e) {
                $this->logger->error(self::LOG_PREFIX . ' deferred increment failed', [
                    'quote_entity_id' => $qid,
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * REST guest checkout passes masked cart id; numeric id is the quote entity id for logged-in flows.
     */
    private function resolveQuoteEntityId(string|int $cartId): int
    {
        $id = (string) $cartId;
        if ($id !== '' && ctype_digit($id)) {
            return (int) $id;
        }
        return $this->maskedQuoteIdToQuoteId->execute($id);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function mergePendingContext(int $quoteEntityId, array $context): void
    {
        if ($context === []) {
            return;
        }
        if (isset($context['message']) && is_string($context['message'])) {
            $context['message'] = $this->truncateMessage($context['message']);
        }
        self::$pendingContext[$quoteEntityId] = array_merge(
            self::$pendingContext[$quoteEntityId] ?? [],
            $context
        );
    }

    private function truncateMessage(string $message): string
    {
        return mb_strlen($message) > 500 ? mb_substr($message, 0, 500) . '…' : $message;
    }

    /**
     * @return list<string>
     */
    private function exceptionTexts(\Throwable $e): array
    {
        $texts = [$e->getMessage()];
        if ($e instanceof LocalizedException) {
            $texts[] = (string) $e->getRawMessage();
        }

        return $texts;
    }

    private function isAddressValidationText(string $text): bool
    {
        $normalized = strtolower($text);

        return str_contains($normalized, 'shipping address information')
            || str_contains($normalized, 'billing address information')
            || (
                str_contains($normalized, '"firstname" is required')
                && (str_contains($normalized, '"street" is required')
                    || str_contains($normalized, '"telephone" is required'))
            );
    }
}
