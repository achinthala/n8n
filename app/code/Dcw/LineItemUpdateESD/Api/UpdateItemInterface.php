<?php
namespace Dcw\LineItemUpdateESD\Api;

use Dcw\LineItemUpdateESD\Api\Data\UpdateItemResponseInterface;

interface UpdateItemInterface
{
     /**
      * Update shipping estimate in pdp_line_item
      *
      * @param int $itemId
      * @param string $estimatedShipDate
      * @return UpdateItemResponseInterface
      */
    public function updateItem(
        int $itemId,
        string $estimatedShipDate
    );
}
