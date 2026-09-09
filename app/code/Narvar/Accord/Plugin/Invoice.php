<?php

namespace Narvar\Accord\Plugin;

use Narvar\Accord\Plugin\InvoiceBase;

class Invoice
{

    private $invoiceBase;

    /**
     * Constructor
     *
     * @param InvoiceBase      $invoiceBase      Base Processor for Invoices
     */
    public function __construct(
        InvoiceBase $invoiceBase
    ) {
        $this->invoiceBase        = $invoiceBase;
    }

    /**
     * Method triggered after Magento\Sales\Model\Order\Invoice class execution
     *
     * @param $invoice magento invoice class instance
     *
     * @return void
     */
    public function afterSave(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        return $this->invoiceBase->afterSave($invoice);
    }
}
