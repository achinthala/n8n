<?php

declare(strict_types=1);

/**
 * @package Dcw RequestQuote
 */

namespace Dcw\RequestQuote\Service;

use Dcw\RequestQuote\Api\Data\QuoteChangeLogInterface;
use Dcw\RequestQuote\Api\QuoteChangeLogRepositoryInterface;
use Dcw\RequestQuote\Model\QuoteChangeLogFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Quote Change Logger Service
 */
class QuoteChangeLogger
{
    /**
     * @param QuoteChangeLogFactory $changeLogFactory
     * @param QuoteChangeLogRepositoryInterface $changeLogRepository
     * @param CustomerSession $customerSession
     * @param AdminSession $adminSession
     * @param Json $json
     */
    public function __construct(
        private readonly QuoteChangeLogFactory $changeLogFactory,
        private readonly QuoteChangeLogRepositoryInterface $changeLogRepository,
        private readonly CustomerSession $customerSession,
        private readonly AdminSession $adminSession,
        private readonly Json $json
    ) {
    }

    /**
     * Log quote change
     *
     * @param int $quoteId
     * @param string $actionType
     * @param array|null $oldValue
     * @param array|null $newValue
     * @param int|null $itemId
     * @return void
     */
    public function logChange(
        int $quoteId,
        string $actionType,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?int $itemId = null
    ): void {
        try {
            /** @var QuoteChangeLogInterface $changeLog */
            $changeLog = $this->changeLogFactory->create();
            $changeLog->setQuoteId($quoteId);
            $changeLog->setActionType($actionType);
            $changeLog->setItemId($itemId);
            
            if ($oldValue !== null) {
                $changeLog->setOldValue($this->json->serialize($oldValue));
            }
            
            if ($newValue !== null) {
                $changeLog->setNewValue($this->json->serialize($newValue));
            }
            
            // Get changed by information
            $changedBy = $this->getChangedBy();
            $changeLog->setChangedBy($changedBy);
            
            // Get customer ID if available
            if ($this->customerSession->isLoggedIn()) {
                $changeLog->setCustomerId((int)$this->customerSession->getCustomerId());
            }
            
            // Get admin details if admin is logged in
            if ($this->adminSession->isLoggedIn()) {
                $adminUser = $this->adminSession->getUser();
                if ($adminUser) {
                    $changeLog->setAdminId((int)$adminUser->getId());
                    $changeLog->setAdminEmail($adminUser->getEmail());
                    
                    // Get admin role
                    $role = $adminUser->getRole();
                    if ($role) {
                        $changeLog->setAdminRole($role->getRoleName());
                    }
                }
            }
            
            $this->changeLogRepository->save($changeLog);
        } catch (\Exception $e) {
            // Continue silently - don't break quote operations
        }
    }

    /**
     * Log item added
     *
     * @param int $quoteId
     * @param int $itemId
     * @param array $itemData
     * @return void
     */
    public function logItemAdded(int $quoteId, int $itemId, array $itemData): void
    {
        $this->logChange(
            $quoteId,
            QuoteChangeLogInterface::ACTION_ITEM_ADDED,
            null,
            $itemData,
            $itemId
        );
    }

    /**
     * Log item removed
     *
     * @param int $quoteId
     * @param int $itemId
     * @param array $itemData
     * @return void
     */
    public function logItemRemoved(int $quoteId, int $itemId, array $itemData): void
    {
        $this->logChange(
            $quoteId,
            QuoteChangeLogInterface::ACTION_ITEM_REMOVED,
            $itemData,
            null,
            $itemId
        );
    }

    /**
     * Log item updated
     *
     * @param int $quoteId
     * @param int $itemId
     * @param array $oldData
     * @param array $newData
     * @return void
     */
    public function logItemUpdated(int $quoteId, int $itemId, array $oldData, array $newData): void
    {
        $this->logChange(
            $quoteId,
            QuoteChangeLogInterface::ACTION_ITEM_UPDATED,
            $oldData,
            $newData,
            $itemId
        );
    }

    /**
     * Log discount changed
     *
     * @param int $quoteId
     * @param array $oldDiscount
     * @param array $newDiscount
     * @return void
     */
    public function logDiscountChanged(int $quoteId, array $oldDiscount, array $newDiscount): void
    {
        $this->logChange(
            $quoteId,
            QuoteChangeLogInterface::ACTION_DISCOUNT_CHANGED,
            $oldDiscount,
            $newDiscount
        );
    }

    /**
     * Log status changed
     *
     * @param int $quoteId
     * @param int $oldStatus
     * @param int $newStatus
     * @return void
     */
    public function logStatusChanged(int $quoteId, int $oldStatus, int $newStatus): void
    {
        $this->logChange(
            $quoteId,
            QuoteChangeLogInterface::ACTION_STATUS_CHANGED,
            ['status' => $oldStatus],
            ['status' => $newStatus]
        );
    }

    /**
     * Log items merged
     *
     * @param int $quoteId
     * @param array $mergedItems Array of items that were merged
     * @param array $mergedFromCart Array of items from cart that were merged
     * @return void
     */
    public function logItemsMerged(int $quoteId, array $mergedItems, array $mergedFromCart): void
    {
        $this->logChange(
            $quoteId,
            QuoteChangeLogInterface::ACTION_ITEMS_MERGED,
            ['cart_items' => $mergedFromCart],
            ['quote_items' => $mergedItems]
        );
    }

    /**
     * Log quote edited
     *
     * @param int $quoteId
     * @param array $oldData
     * @param array $newData
     * @return void
     */
    public function logQuoteEdited(int $quoteId, array $oldData, array $newData): void
    {
        $this->logChange(
            $quoteId,
            QuoteChangeLogInterface::ACTION_QUOTE_EDITED,
            $oldData,
            $newData
        );
    }

    /**
     * Get changed by information
     *
     * @return string
     */
    private function getChangedBy(): string
    {
        // Check if admin is logged in
        if ($this->adminSession->isLoggedIn()) {
            $adminUser = $this->adminSession->getUser();
            return $adminUser ? $adminUser->getUserName() : 'Admin';
        }
        
        // Check if customer is logged in
        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomer()->getEmail();
        }
        
        return 'Guest';
    }
}

