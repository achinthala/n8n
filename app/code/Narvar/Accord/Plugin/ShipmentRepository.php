<?php

namespace Narvar\Accord\Plugin;

use Narvar\Accord\Plugin\ShipmentBase;

class ShipmentRepository
{

    private $shipmentBase;

    /**
     * Constructor
     *
     * @param ShipmentBase        $shipmentBase        Base Processor for Shipments
     */
    public function __construct(
        ShipmentBase $shipmentBase
    ) {
        $this->shipmentBase        = $shipmentBase;
    }

    public function afterSave(
        \Magento\Sales\Model\Order\ShipmentRepository $shipmentRepository,
        $result,
        $shipment
    ) {
          $this->shipmentBase->afterSave($shipment);
          return $result;
    }
}
