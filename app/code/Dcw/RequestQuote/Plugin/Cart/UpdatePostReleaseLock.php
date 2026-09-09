<?php
/**
 * Plugin to release quote lock when customer submits quote
 */

namespace Dcw\RequestQuote\Plugin\Cart;

use Dcw\ShoppingCart\Controller\Cart\UpdatePost;
use Dcw\RequestQuote\Service\QuoteLockService;
use Magento\Framework\Session\SessionManagerInterface;

class UpdatePostReleaseLock
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
     * @param QuoteLockService $quoteLockService
     * @param SessionManagerInterface $sessionManager
     */
    public function __construct(
        QuoteLockService $quoteLockService,
        SessionManagerInterface $sessionManager
    ) {
        $this->quoteLockService = $quoteLockService;
        $this->sessionManager = $sessionManager;
    }

    /**
     * Release lock after quote is submitted
     *
     * @param UpdatePost $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterExecute(UpdatePost $subject, $result)
    {
        try {
            // Get quote ID from request (relation_parent_id if editing)
            $relationParentId = $subject->getRequest()->getParam('relation_parent_id');
            $editQuoteId = $subject->getRequest()->getParam('edit_quote_id');
            $quoteId = $relationParentId ?: $editQuoteId;
            
            if ($quoteId) {
                $sessionId = $this->sessionManager->getSessionId();
                $this->quoteLockService->releaseLock((int)$quoteId, $sessionId);
            }
        } catch (\Exception $e) {
            // Silent fail - don't break quote submission if lock release fails
        }

        return $result;
    }
}

