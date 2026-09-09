<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Controller\Adminhtml;

use Magento\Backend\App\Action;

abstract class AbstractAdminAction extends Action
{
    public const ADMIN_RESOURCE = 'Dcw_ShipRegionAvailability::ship_region';
}
