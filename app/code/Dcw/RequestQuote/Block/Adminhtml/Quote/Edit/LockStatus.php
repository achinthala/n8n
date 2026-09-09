<?php
/**
 * Quote Lock Status Block
 */

namespace Dcw\RequestQuote\Block\Adminhtml\Quote\Edit;

use Magento\Backend\Block\Template;
use Dcw\RequestQuote\Service\QuoteLockService;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Backend\Model\Session;

class LockStatus extends Template
{
    /**
     * @var QuoteLockService
     */
    protected $quoteLockService;

    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var Session
     */
    protected $backendSession;

    /**
     * @param Template\Context $context
     * @param QuoteLockService $quoteLockService
     * @param SessionManagerInterface $sessionManager
     * @param Session $backendSession
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        QuoteLockService $quoteLockService,
        SessionManagerInterface $sessionManager,
        Session $backendSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quoteLockService = $quoteLockService;
        $this->sessionManager = $sessionManager;
        $this->backendSession = $backendSession;
    }

    /**
     * Get quote ID from request
     *
     * @return int|null
     */
    public function getQuoteId()
    {
        return (int) $this->getRequest()->getParam('quote_id');
    }

    /**
     * Acquire lock when block is rendered (page loads)
     *
     * @return string
     */
    protected function _toHtml()
    {
        $quoteId = $this->getQuoteId();
        if ($quoteId) {
            try {
                $sessionId = $this->sessionManager->getSessionId();
                if (!$sessionId) {
                    // Try to get from backend session
                    $sessionId = $this->backendSession->getSessionId();
                }
                $this->quoteLockService->acquireLock($quoteId, $sessionId);
            } catch (\Exception $e) {
                // Silent fail - don't break page rendering
            }
        }
        return parent::_toHtml();
    }

    /**
     * Get lock status
     *
     * @return array
     */
    public function getLockStatus()
    {
        $quoteId = $this->getQuoteId();
        if (!$quoteId) {
            return ['is_locked' => false];
        }

        $sessionId = $this->sessionManager->getSessionId();
        return $this->quoteLockService->getLockStatus($quoteId, $sessionId);
    }

    /**
     * Get lock status URL
     *
     * @return string
     */
    public function getLockStatusUrl()
    {
        return $this->getUrl('dcwrequestquote/quote_lock/status', ['quote_id' => $this->getQuoteId()]);
    }

    /**
     * Get acquire lock URL
     *
     * @return string
     */
    public function getAcquireLockUrl()
    {
        return $this->getUrl('dcwrequestquote/quote_lock/acquire', ['quote_id' => $this->getQuoteId()]);
    }

    /**
     * Get release lock URL
     *
     * @return string
     */
    public function getReleaseLockUrl()
    {
        return $this->getUrl('dcwrequestquote/quote_lock/release', ['quote_id' => $this->getQuoteId()]);
    }


    /**
     * Get force unlock URL
     *
     * @return string
     */
    public function getForceUnlockUrl()
    {
        return $this->getUrl('dcwrequestquote/quote_lock/forceUnlock', ['quote_id' => $this->getQuoteId()]);
    }

    /**
     * Get current session ID
     *
     * @return string|null
     */
    public function getSessionId()
    {
        $sessionId = $this->sessionManager->getSessionId();
        if (!$sessionId) {
            $sessionId = $this->backendSession->getSessionId();
        }
        return $sessionId;
    }
}

