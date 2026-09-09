<?php
namespace Dcw\TaxCertificate\Cron;

use Psr\Log\LoggerInterface;
use Dcw\TaxCertificate\Model\CertificateProcessor;

class ProcessCertificates
{
    protected $logger;
    protected $certificateProcessor;

    public function __construct(
        LoggerInterface $logger,
        CertificateProcessor $certificateProcessor
    ) {
        $this->logger = $logger;
        $this->certificateProcessor = $certificateProcessor;
    }

    public function execute()
    {
        $this->createLog('Processing tax certificates...');
        $this->certificateProcessor->process();
        return $this;
    }

    public function createLog($msg){
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/CronTaxCertificate.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
}
