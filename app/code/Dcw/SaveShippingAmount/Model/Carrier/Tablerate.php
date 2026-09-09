<?php
namespace Dcw\SaveShippingAmount\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;

class Tablerate extends \Magento\OfflineShipping\Model\Carrier\Tablerate
{
    /**
     * @var MethodFactory
     */
    protected $rateMethodFactory;

    /**
     * @param MethodFactory $rateMethodFactory
     * @param ... other dependencies
     */
    public function __construct(
        MethodFactory $rateMethodFactory,
        // other dependencies
    ) {
        $this->rateMethodFactory = $rateMethodFactory;
        parent::__construct(/* pass other dependencies */);
    }

    /**
     * Collect rates based on the rate request
     *
     * @param RateRequest $request
     * @return \Magento\Quote\Model\Quote\Address\RateResult|false
     */
    public function collectRates(RateRequest $request)
    {
        $result = $this->rateResultFactory->create();
        $method = $this->rateMethodFactory->create();

        // Set a fixed shipping price
        $shippingPrice = 50;
        $this->createLog("amount are set mmmm");
        // Configure shipping method
        $method->setCarrier($this->_code);
        $method->setCarrierTitle(__('Custom Shipping'));
        $method->setMethod('custom_rate');
        $method->setMethodTitle(__('Fixed Rate'));
        $method->setPrice($shippingPrice);
        $method->setCost($shippingPrice);

        // Add the shipping method to the result
        $result->append($method);

        return $result;
    }

    function createLog($msg){
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/FixeShipping.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
}
