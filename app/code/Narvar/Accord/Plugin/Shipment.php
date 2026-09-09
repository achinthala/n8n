<?php

namespace Narvar\Accord\Plugin;

use Narvar\Accord\Plugin\ShipmentBase;

class Shipment
{

    private $shipmentBase;

    /**
     * Constructor
     *
     * @param ShipmentBase      $shipmentBase      Base Processor for Shipments
     */
    public function __construct(
        ShipmentBase $shipmentBase
    ) {
        $this->shipmentBase        = $shipmentBase;
    }

    /**
     * Method triggered after Magento\Sales\Model\Order\Shipment class execution
     *
     * @param $shipment magento shipment class instance
     *
     * @return void
     */
    public function afterSave(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        return $this->shipmentBase->afterSave($shipment);
    }
}
