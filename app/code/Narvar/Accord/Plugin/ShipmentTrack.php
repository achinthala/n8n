<?php

namespace Narvar\Accord\Plugin;

use Narvar\Accord\Plugin\ShipmentBase;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Narvar\Accord\Helper\RepositoryHelper;

class ShipmentTrack
{

    private $shipmentBase;

    private $repositoryHelper;

    /**
     * Constructor
     *
     * @param ShipmentBase      $shipmentBase      Base Processor for Shipments
     * @param RepositoryHelper              $repositoryHelper
     */
    public function __construct(
        ShipmentBase $shipmentBase,
        RepositoryHelper $repositoryHelper
    ) {
        $this->shipmentBase = $shipmentBase;
        $this->repositoryHelper               = $repositoryHelper;
    }

    public function afterSave(
        $trackRepository,
        $result,
        $track
    ) {
        try {
            $this->shipmentBase->afterSave($this->repositoryHelper->getShipmentById($track->getParentId()));
        } finally {
            return $result;
        }
    }
}
