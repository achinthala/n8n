<?php

namespace Narvar\Accord\Plugin;

use Narvar\Accord\Helper\CustomLogger;
use Narvar\Accord\Helper\Processor;
use Narvar\Accord\Helper\Util;
use Narvar\Accord\Helper\NoFlakeLogger;

class InvoiceBase
{

    private $logger;

    private $processor;

    private $util;

    private $noFlakeLogger;

    public function __construct(
        CustomLogger $logger,
        Processor $processor,
        Util $util,
        NoFlakeLogger $noFlakeLogger
    ) {
        $this->logger            = $logger;
        $this->processor         = $processor;
        $this->util              = $util;
        $this->noFlakeLogger     = $noFlakeLogger;
    }

    public function afterSave($invoice)
    {
        $orderId = $invoice->getOrder()->getIncrementId();
        $storeId = $invoice->getOrder()->getStoreId();
        $eventName = 'narvar_invoice_plugin';
        try {
            $this->util->logMetadata($orderId, $storeId, $eventName, 'start');
            $accordEnabled = $this->util->isNarvarAccordEnabled($storeId);
            if ($accordEnabled) {
                $this->util->logMetadata($orderId, $storeId, $eventName, 'extension is enabled');
                $retailerMoniker = $this->util->getRetailerMoniker($storeId);
                $this->util->logMetadata($orderId, $storeId, $eventName, 'retailer fetched');
                $narvarInvoiceObject = $this->util->getNarvarOrderObject(
                    $invoice->getOrder(),
                    $eventName
                );
                
                $this->util->logMetadata($orderId, $storeId, $eventName, 'order object fetched');
                $narvarInvoiceObject['invoice'] = $this->util->getInvoiceData($invoice, $storeId);
                $this->util->logMetadata($orderId, $storeId, $eventName, 'invoice object fetched');
                $this->processor->sendPluginData($narvarInvoiceObject, $retailerMoniker, $eventName);
                $this->util->logMetadata($orderId, $storeId, $eventName, 'invoice sent to narvar');
                $this->noFlakeLogger->logNoFlakeData(
                    $narvarInvoiceObject,
                    $eventName,
                    $retailerMoniker
                );
                $this->util->logMetadata($orderId, $storeId, $eventName, 'invoice sent to narvar data lake');
            } else {
                $this->util->logMetadata(
                    $orderId,
                    $storeId,
                    $eventName,
                    'end - Narvar Accord Not Configured for this Store Id'
                );
            }
        } catch (\Exception $ex) {
            $this->util->handleException($ex, $orderId, $storeId, $eventName);
        } finally {
            return $invoice;
        }
    }
}
