<?php
/**
 * Quote Lock Status Block (Cart Page)
 */

namespace Dcw\RequestQuote\Block\Cart;

use Magento\Framework\View\Element\Template;
use Dcw\RequestQuote\Service\QuoteLockService;
use Magento\Framework\Session\SessionManagerInterface;
use Amasty\RequestQuote\Model\Quote\Session as QuoteSession;

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
     * @var QuoteSession
     */
    protected $quoteSession;

    /**
     * @param Template\Context $context
     * @param QuoteLockService $quoteLockService
     * @param SessionManagerInterface $sessionManager
     * @param QuoteSession $quoteSession
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        QuoteLockService $quoteLockService,
        SessionManagerInterface $sessionManager,
        QuoteSession $quoteSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quoteLockService = $quoteLockService;
        $this->sessionManager = $sessionManager;
        $this->quoteSession = $quoteSession;
    }

    /**
     * Get quote ID from session
     *
     * @return int|null
     */
    public function getQuoteId()
    {
        $quote = $this->quoteSession->getQuote();
        if ($quote && $quote->getId()) {
            // Get the original quote ID being edited (relation_parent_id)
            $relationParentId = $quote->getRelationParentId();
            return $relationParentId ?: $quote->getId();
        }
        return null;
    }

    /**
     * Get release lock URL
     *
     * @return string
     */
    public function getReleaseLockUrl()
    {
        $quoteId = $this->getQuoteId();
        if ($quoteId) {
            return $this->getUrl('requestquote_edit/quote_lock/release', ['quote_id' => $quoteId]);
        }
        return '';
    }

    /**
     * Get update activity URL
     *
     * @return string
     */
    public function getUpdateActivityUrl()
    {
        $quoteId = $this->getQuoteId();
        if ($quoteId) {
            return $this->getUrl('requestquote_edit/quote_lock/updateActivity', ['quote_id' => $quoteId]);
        }
        return '';
    }
}

