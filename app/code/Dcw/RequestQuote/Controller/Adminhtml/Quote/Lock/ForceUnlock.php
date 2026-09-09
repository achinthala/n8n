<?php
/**
 * Force Unlock Quote Controller
 */

namespace Dcw\RequestQuote\Controller\Adminhtml\Quote\Lock;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Dcw\RequestQuote\Service\QuoteLockService;

class ForceUnlock extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Dcw_RequestQuote::quote_lock';

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var QuoteLockService
     */
    protected $quoteLockService;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param QuoteLockService $quoteLockService
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        QuoteLockService $quoteLockService
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteLockService = $quoteLockService;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $quoteId = (int) $this->getRequest()->getParam('quote_id');
        
        if (!$quoteId) {
            $result->setData(['success' => false, 'message' => __('Quote ID is required.')]);
            return $result;
        }

        $unlocked = $this->quoteLockService->forceUnlock($quoteId);

        $result->setData([
            'success' => $unlocked,
            'message' => $unlocked ? __('Quote lock has been force unlocked successfully.') : __('No lock found or already unlocked.')
        ]);

        return $result;
    }
}

