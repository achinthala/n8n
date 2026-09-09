<?php

namespace Narvar\Accord\Plugin;

use Narvar\Accord\Plugin\InvoiceBase;

class InvoiceRepository
{

    private $invoiceBase;

    /**
     * Constructor
     *
     * @param InvoiceBase        $invoiceBase        Base Processor for Invoices
     */
    public function __construct(
        InvoiceBase $invoiceBase
    ) {
        $this->invoiceBase        = $invoiceBase;
    }

    public function afterSave(
        \Magento\Sales\Model\Order\InvoiceRepository $invoiceRepository,
        $result,
        $invoice
    ) {
          $this->invoiceBase->afterSave($invoice);
          return $result;
    }
}
