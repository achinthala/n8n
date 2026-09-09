<?php
declare(strict_types=1);

namespace Dcw\Checkout\Controller\Checkout;
 
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\JsonFactory;
 
class Index extends Action
{
    public function __construct(
        Context $context,
        private readonly CheckoutSession $checkoutSession,
        private readonly JsonFactory $resultJsonFactory
    )
    {
        parent::__construct($context);
    }
 
    public function execute()
    {
        $checkForError = $this->getRequest()->getParam('checkForError');

        if ($checkForError == 1) {
            $getApiFailResponseCheck = $this->checkoutSession->getApiFailResponseCheck();
            $getApiResponseAmount = $this->checkoutSession->getApiResponseAmount();

            if ($getApiFailResponseCheck == 1) {
                $output = [
                    'checkForError' => 1
                ];
            } else {
                $output = [
                    'checkForError' => 0,
                    'apiResponseAmount' => $getApiResponseAmount
                ];
            }

            $resultJson = $this->resultJsonFactory->create();

            return $resultJson->setData($output);
        }

		$calculateShipping = $this->getRequest()->getParam('calculateShipping');
        
        if ($calculateShipping) {
            $this->checkoutSession->setCalculateShipping(1);
        }
    }
}
