<?php
/**
 * Quote Lock Status Block (Frontend)
 */

namespace Dcw\RequestQuote\Block\Account\Quote;

use Magento\Framework\View\Element\Template;
use Dcw\RequestQuote\Service\QuoteLockService;
use Magento\Framework\Session\SessionManagerInterface;

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
     * @param Template\Context $context
     * @param QuoteLockService $quoteLockService
     * @param SessionManagerInterface $sessionManager
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        QuoteLockService $quoteLockService,
        SessionManagerInterface $sessionManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quoteLockService = $quoteLockService;
        $this->sessionManager = $sessionManager;
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
        return $this->getUrl('requestquote_edit/quote_lock/status', ['quote_id' => $this->getQuoteId()]);
    }

    /**
     * Get acquire lock URL
     *
     * @return string
     */
    public function getAcquireLockUrl()
    {
        return $this->getUrl('requestquote_edit/quote_lock/acquire', ['quote_id' => $this->getQuoteId()]);
    }


    /**
     * Get release lock URL
     *
     * @return string
     */
    public function getReleaseLockUrl()
    {
        return $this->getUrl('requestquote_edit/quote_lock/release', ['quote_id' => $this->getQuoteId()]);
    }
}

