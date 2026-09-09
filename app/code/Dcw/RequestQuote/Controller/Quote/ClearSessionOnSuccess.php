<?php
/**
 * Clears Amasty quote session when user lands on quote success page.
 * Returns section data in same request to avoid CDN cache + session replication
 * on load-balanced production (single request = correct data guaranteed).
 */
declare(strict_types=1);

namespace Dcw\RequestQuote\Controller\Quote;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Amasty\RequestQuote\Model\Quote\Session as AmastyQuoteSession;
use Dcw\RequestQuote\ViewModel\ActiveCartIcon;
use Magento\Customer\CustomerData\SectionPoolInterface;
use Psr\Log\LoggerInterface;

class ClearSessionOnSuccess implements HttpGetActionInterface
{
    private const SECTIONS = ['cart', 'quotecart', 'messages', 'wp_ga4'];

    public function __construct(
        private readonly JsonFactory $resultJsonFactory,
        private readonly CheckoutSession $checkoutSession,
        private readonly AmastyQuoteSession $amastyQuoteSession,
        private readonly SectionPoolInterface $sectionPool,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $result->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate', true);
        $result->setHeader('Pragma', 'no-cache', true);

        $this->logger->info('ClearSessionOnSuccess: Quote success page session clear started');

        try {
            $this->checkoutSession->setData(ActiveCartIcon::getSessionKey(), ActiveCartIcon::getTypeCart());
            $this->amastyQuoteSession->setQuoteId(null);

            $reflection = new \ReflectionClass($this->amastyQuoteSession);
            $this->logger->info('ClearSessionOnSuccess: Amasty quote session reflection', [
                'has_quote_property' => $reflection->hasProperty('_quote'),
                'quote_id_before' => $this->amastyQuoteSession->getQuoteId(),
            ]);
            if ($reflection->hasProperty('_quote')) {
                $property = $reflection->getProperty('_quote');
                $property->setAccessible(true);
                $oldQuote = $property->getValue($this->amastyQuoteSession);
                $property->setValue($this->amastyQuoteSession, null);
                $this->logger->info('ClearSessionOnSuccess: Cleared Amasty _quote via reflection', [
                    'old_quote_id' => $oldQuote?->getId(),
                ]);
            } else {
                $this->logger->warning('ClearSessionOnSuccess: Amasty session has no _quote property');
            }

            $this->amastyQuoteSession->flushSection('quotecart');

            // Also clear checkout session cached _quote (same as UpdatePost)
            try {
                $checkoutReflection = new \ReflectionClass($this->checkoutSession);
                if ($checkoutReflection->hasProperty('_quote')) {
                    $prop = $checkoutReflection->getProperty('_quote');
                    $prop->setAccessible(true);
                    $prop->setValue($this->checkoutSession, null);
                }
            } catch (\Exception $e) {
                $this->logger->warning('ClearSessionOnSuccess: Failed to clear checkout _quote', ['exception' => $e->getMessage()]);
            }

            $this->logger->info('ClearSessionOnSuccess: Quote session cleared successfully');

            // Load section data in same request (avoids CDN cache + session replication)
            $response = $this->sectionPool->getSectionsData(self::SECTIONS, true);
            $response['active_cart_icon'] = ActiveCartIcon::getTypeCart();
            $response['success'] = true;

            // Force empty quotecart - Amasty may auto-reload quote from DB via getActiveForCustomer
            if (isset($response['quotecart']['data'])) {
                $response['quotecart']['data'] = array_merge($response['quotecart']['data'], [
                    'items' => [],
                    'summary_count' => 0,
                ]);
            }

            $result->setData($response);
        } catch (\Exception $e) {
            $this->logger->error('ClearSessionOnSuccess: Failed to clear session', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $result->setData(['success' => false, 'error' => $e->getMessage()]);
        }

        return $result;
    }
}
