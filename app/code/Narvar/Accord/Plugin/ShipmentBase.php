<?php

namespace Narvar\Accord\Plugin;

use Narvar\Accord\Helper\CustomLogger;
use Narvar\Accord\Helper\Processor;
use Narvar\Accord\Helper\Util;
use Narvar\Accord\Helper\NoFlakeLogger;

class ShipmentBase
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

    public function afterSave($shipment)
    {
        $orderId    = $shipment->getOrder()->getIncrementId();
        $storeId    = $shipment->getOrder()->getStoreId();
        $eventName  = "narvar_shipment_plugin";
        try {
            $this->util->logMetadata($orderId, $storeId, $eventName, 'start');
            $accordEnabled = $this->util->isNarvarAccordEnabled($storeId);
            if ($accordEnabled) {
                $this->util->logMetadata($orderId, $storeId, $eventName, 'extension is enabled');
                $retailerMoniker = $this->util->getRetailerMoniker($storeId);
                $this->util->logMetadata($orderId, $storeId, $eventName, 'retailer fetched');
                $narvarShipmentObject = $this->util->getNarvarOrderObject(
                    $shipment->getOrder(),
                    $eventName
                );
                $this->util->logMetadata($orderId, $storeId, $eventName, 'order object fetched');
                $narvarShipmentObject['shipment'] = $this->util->getShipmentData($shipment, $storeId);
                $this->util->logMetadata($orderId, $storeId, $eventName, 'shipment object fetched');
                $this->processor->sendPluginData($narvarShipmentObject, $retailerMoniker, $eventName);
                $this->util->logMetadata($orderId, $storeId, $eventName, 'shipemnt sent to narvar');
                $this->noFlakeLogger->logNoFlakeData(
                    $narvarShipmentObject,
                    $eventName,
                    $retailerMoniker
                );
                $this->util->logMetadata($orderId, $storeId, $eventName, 'shipment sent to narvar data lake');
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
            return $shipment;
        }
    }
}
