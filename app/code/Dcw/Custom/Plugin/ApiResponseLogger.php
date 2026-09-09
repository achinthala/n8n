<?php
declare(strict_types=1);

namespace Dcw\Custom\Plugin;

use Psr\Log\LoggerInterface;

class ApiResponseLogger
{
    /**
     * Product sku to debug.
     */
    private const SKU_TO_DEBUG  = '3563_11647_49104';

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Around plugin to log response data.
     *
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $subject
     * @param \Closure $proceed
     * @param string $sku
     * @return mixed
     */
    public function aroundGetById(
        \Magento\Catalog\Api\ProductRepositoryInterface $subject,
        \Closure $proceed,
        $sku
    ) {

        if ($sku != self::SKU_TO_DEBUG) {
            return $proceed($sku);
        }

        try {
            if ($sku) {
                $responseData = [];
                // Proceed with the original method call
                $result = $proceed($sku);

                if ($result->getSku() == self::SKU_TO_DEBUG) {
                    try {
                        // Log the response
                        $responseData['sku'] = $result->getSku();
                        $result->setIncstoresPimInstallationDescription('');
                        $result->setIncstoresPimLongDescription('');
                        $result->setIncstoresPimMaintenanceDescription('');
                        $result->setIncstoresPimShippingDescription('');
                        $result->setMediaGallery('');

                        $responseData['response'] = $result->getData();
                        $this->createLog('API Response for SKU:'.json_encode($responseData));

                        $result = $proceed($sku);
                        return $result;
                    } catch (\Exception $e) {
                        // Log the exception
                        $responseData['sku'] = $result->getSku();
                        $responseData['error'] = $e->getMessage();
                        $this->createLog('Error fetching product: '.json_encode($responseData));
                        throw $e;
                    }
                }
            }
        } catch (\Exception $e) {
            // Log any exception that occurs
            $this->createLog('Error exception: '.$e->getMessage());
        }
    }

    public function createLog($msg)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/logProductApi.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
}

