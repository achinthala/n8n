<?php
declare(strict_types=1);

namespace Dcw\PurchaseOrderReview\Block\Form;

use Magento\Payment\Block\Form;

class PoGateway extends Form
{
    protected $_template = 'Dcw_PurchaseOrderReview::form/po_gateway.phtml';
}
