<?php
/**
 * Acquire Quote Lock Controller
 */

namespace Dcw\RequestQuote\Controller\Adminhtml\Quote\Lock;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Dcw\RequestQuote\Service\QuoteLockService;
use Magento\Framework\Session\SessionManagerInterface;

class Acquire extends Action
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
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param QuoteLockService $quoteLockService
     * @param SessionManagerInterface $sessionManager
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        QuoteLockService $quoteLockService,
        SessionManagerInterface $sessionManager
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteLockService = $quoteLockService;
        $this->sessionManager = $sessionManager;
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

        $sessionId = $this->sessionManager->getSessionId();
        $acquired = $this->quoteLockService->acquireLock($quoteId, $sessionId);

        if ($acquired) {
            $result->setData(['success' => true, 'message' => __('Lock acquired successfully.')]);
        } else {
            $lockStatus = $this->quoteLockService->getLockStatus($quoteId, $sessionId);
            $result->setData([
                'success' => false,
                'message' => __('Quote is already locked by another user.'),
                'lock_status' => $lockStatus
            ]);
        }

        return $result;
    }
}

