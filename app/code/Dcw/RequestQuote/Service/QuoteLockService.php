<?php
/**
 * Quote Lock Service
 */

namespace Dcw\RequestQuote\Service;

use Dcw\RequestQuote\Model\QuoteLockFactory;
use Dcw\RequestQuote\Model\ResourceModel\QuoteLock\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Customer\Model\Session as CustomerSession;
use Psr\Log\LoggerInterface;

class QuoteLockService
{
    /**
     * Configuration paths
     */
    const XML_PATH_LOCK_TIMEOUT = 'requestquote/quote_lock/lock_timeout';

    /**
     * Default values
     */
    const DEFAULT_LOCK_TIMEOUT = 300;

    /**
     * @var QuoteLockFactory
     */
    protected $quoteLockFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var AdminSession
     */
    protected $adminSession;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param QuoteLockFactory $quoteLockFactory
     * @param CollectionFactory $collectionFactory
     * @param DateTime $dateTime
     * @param ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerRepositoryInterface $customerRepository
     * @param AdminSession $adminSession
     * @param CustomerSession $customerSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        QuoteLockFactory $quoteLockFactory,
        CollectionFactory $collectionFactory,
        DateTime $dateTime,
        ResourceConnection $resourceConnection,
        ScopeConfigInterface $scopeConfig,
        CustomerRepositoryInterface $customerRepository,
        AdminSession $adminSession,
        CustomerSession $customerSession,
        LoggerInterface $logger
    ) {
        $this->quoteLockFactory = $quoteLockFactory;
        $this->collectionFactory = $collectionFactory;
        $this->dateTime = $dateTime;
        $this->resourceConnection = $resourceConnection;
        $this->scopeConfig = $scopeConfig;
        $this->customerRepository = $customerRepository;
        $this->adminSession = $adminSession;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
    }

    /**
     * Acquire lock for a quote
     *
     * @param int $quoteId
     * @param string $sessionId
     * @return bool
     */
    public function acquireLock($quoteId, $sessionId = null)
    {
        try {
            // Clean up expired locks first
            $this->cleanupExpiredLocks();

            // Get current user info first
            $lockedByType = 'customer';
            $lockedById = $this->customerSession->getCustomerId();
            $lockedByName = '';

            if ($this->adminSession->isLoggedIn()) {
                $lockedByType = 'admin';
                $user = $this->adminSession->getUser();
                $lockedById = $user->getId();
                $lockedByName = $user->getUserName();
            } elseif ($lockedById) {
                try {
                    $customer = $this->customerRepository->getById($lockedById);
                    $lockedByName = trim($customer->getFirstname() . ' ' . $customer->getLastname());
                } catch (\Exception $e) {
                    $lockedByName = '';
                }
            }

            if (!$lockedById) {
                return false;
            }

            // IMPORTANT: First check if quote is locked by a DIFFERENT type (admin vs customer)
            // If a customer has locked it, admin should NOT be able to acquire/update
            // If an admin has locked it, customer should NOT be able to acquire/update
            $otherTypeLock = null;
            $oppositeType = ($lockedByType === 'admin') ? 'customer' : 'admin';
            $otherTypeLock = $this->getActiveLockByType($quoteId, $oppositeType);
            
            if ($otherTypeLock) {
                // Quote is locked by opposite type - deny acquisition
                return false;
            }
            
            // Check if quote is already locked by the same user and type
            // First check if there's a lock for this specific session and type
            $existingLock = null;
            if ($sessionId) {
                $existingLock = $this->getActiveLockByType($quoteId, $lockedByType, $sessionId);
            }
            
            // If no lock for this session, check for any active lock by this user and type
            if (!$existingLock) {
                $existingLock = $this->getActiveLockByType($quoteId, $lockedByType);
                // Double-check: ensure the lock is by the same user
                if ($existingLock && $existingLock->getLockedById() != $lockedById) {
                    $existingLock = null;
                }
            }
            
            if ($existingLock) {
                // Found existing lock by same user and type - update it
                $existingSessionId = $existingLock->getSessionId();
                if ($sessionId && $sessionId !== $existingSessionId) {
                    $existingLock->setSessionId($sessionId);
                }
                $existingLock->setLockedByName($lockedByName);
                $existingLock->setLastActivity($this->dateTime->gmtDate());
                $existingLock->save();
            } else {
                // No existing lock by this user/type - create new one
                $lock = $this->quoteLockFactory->create();
                $lock->setQuoteId($quoteId);
                $lock->setLockedByType($lockedByType);
                $lock->setLockedById($lockedById);
                $lock->setLockedByName($lockedByName);
                if ($sessionId) {
                    $lock->setSessionId($sessionId);
                }
                $lock->setLastActivity($this->dateTime->gmtDate());
                $lock->save();
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error acquiring quote lock: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Release lock for a quote
     *
     * @param int $quoteId
     * @param string $sessionId
     * @return bool
     */
    public function releaseLock($quoteId, $sessionId = null)
    {
        try {
            $lock = $this->getActiveLock($quoteId, $sessionId);
            if ($lock) {
                $lock->delete();
                return true;
            }
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error releasing quote lock: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Force unlock a quote (admin only - removes all locks for the quote)
     *
     * @param int $quoteId
     * @return bool
     */
    public function forceUnlock($quoteId)
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('quote_lock');
            
            $deleted = $connection->delete(
                $tableName,
                ['quote_id = ?' => $quoteId]
            );
            
            return $deleted > 0;
        } catch (\Exception $e) {
            $this->logger->error('Error force unlocking quote: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update last activity for a lock
     * Only updates locks that match the current user's type (admin or customer)
     *
     * @param int $quoteId
     * @param string $sessionId
     * @return bool
     */
    public function updateActivity($quoteId, $sessionId = null)
    {
        try {
            // Determine current user type
            $lockedByType = 'customer';
            $lockedById = $this->customerSession->getCustomerId();
            
            if ($this->adminSession->isLoggedIn()) {
                $lockedByType = 'admin';
                $user = $this->adminSession->getUser();
                $lockedById = $user->getId();
            }
            
            if (!$lockedById) {
                return false;
            }
            
            // Only get locks of the same type
            $lock = null;
            if ($sessionId) {
                $lock = $this->getActiveLockByType($quoteId, $lockedByType, $sessionId);
            }
            
            if (!$lock) {
                $lock = $this->getActiveLockByType($quoteId, $lockedByType);
            }
            
            // Only update if lock exists and is by the same user
            if ($lock && $lock->getLockedById() == $lockedById && $lock->getLockedByType() == $lockedByType) {
                $lock->setLastActivity($this->dateTime->gmtDate());
                $lock->save();
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error updating lock activity: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get active lock for a quote
     *
     * @param int $quoteId
     * @param string $sessionId
     * @return \Dcw\RequestQuote\Model\QuoteLock|null
     */
    public function getActiveLock($quoteId, $sessionId = null)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('quote_id', $quoteId);
        
        if ($sessionId) {
            $collection->addFieldToFilter('session_id', $sessionId);
        }

        $lock = $collection->getFirstItem();
        
        if ($lock->getId()) {
            // Check if lock is expired
            $lastActivity = strtotime($lock->getLastActivity());
            $now = $this->dateTime->timestamp();
            $lockTimeout = $this->getLockTimeout();
            
            if (($now - $lastActivity) > $lockTimeout) {
                // Lock expired, delete it
                $lock->delete();
                return null;
            }
            
            return $lock;
        }

        return null;
    }

    /**
     * Get active lock for a quote by type (admin or customer)
     *
     * @param int $quoteId
     * @param string $lockedByType
     * @param string|null $sessionId
     * @return \Dcw\RequestQuote\Model\QuoteLock|null
     */
    public function getActiveLockByType($quoteId, $lockedByType, $sessionId = null)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('quote_id', $quoteId);
        $collection->addFieldToFilter('locked_by_type', $lockedByType);
        
        if ($sessionId) {
            $collection->addFieldToFilter('session_id', $sessionId);
        }

        $lock = $collection->getFirstItem();
        
        if ($lock->getId()) {
            // Check if lock is expired
            $lastActivity = strtotime($lock->getLastActivity());
            $now = $this->dateTime->timestamp();
            $lockTimeout = $this->getLockTimeout();
            
            if (($now - $lastActivity) > $lockTimeout) {
                // Lock expired, delete it
                $lock->delete();
                return null;
            }
            
            return $lock;
        }

        return null;
    }

    /**
     * Get absolute lock status for a quote (for grid/collection display)
     * This method doesn't check if lock is by current user - just returns if quote is locked
     * Use this in contexts where session might not be available (e.g., cached collections)
     *
     * @param int $quoteId
     * @return array
     */
    public function getAbsoluteLockStatus($quoteId)
    {
        $lock = $this->getActiveLock($quoteId);
        
        if (!$lock) {
            return [
                'is_locked' => false,
                'locked_by_type' => null,
                'locked_by_name' => null,
                'locked_at' => null,
                'session_id' => null
            ];
        }

        return [
            'is_locked' => true,
            'locked_by_type' => $lock->getLockedByType(),
            'locked_by_name' => $lock->getLockedByName(),
            'locked_at' => $lock->getLockedAt(),
            'session_id' => $lock->getSessionId()
        ];
    }

    /**
     * Get lock status for a quote
     *
     * @param int $quoteId
     * @param string $sessionId
     * @return array
     */
    public function getLockStatus($quoteId, $sessionId = null)
    {
        $lock = $this->getActiveLock($quoteId);
        
        if (!$lock) {
            return [
                'is_locked' => false,
                'locked_by_type' => null,
                'locked_by_name' => null,
                'locked_at' => null
            ];
        }

        // Check if it's locked by current user
        $isCurrentUser = false;
        if ($sessionId && $lock->getSessionId() === $sessionId) {
            $isCurrentUser = true;
        } elseif ($this->adminSession->isLoggedIn() && 
                  $lock->getLockedByType() === 'admin' && 
                  $lock->getLockedById() == $this->adminSession->getUser()->getId()) {
            $isCurrentUser = true;
        } elseif ($this->customerSession->getCustomerId() && 
                  $lock->getLockedByType() === 'customer' && 
                  $lock->getLockedById() == $this->customerSession->getCustomerId()) {
            $isCurrentUser = true;
        }

        return [
            'is_locked' => !$isCurrentUser,
            'locked_by_type' => $lock->getLockedByType(),
            'locked_by_name' => $lock->getLockedByName(),
            'locked_at' => $lock->getLockedAt(),
            'session_id' => $lock->getSessionId(),
            'is_current_user' => $isCurrentUser
        ];
    }

    /**
     * Clean up expired locks
     *
     * @return int Number of deleted locks
     */
    public function cleanupExpiredLocks()
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('quote_lock');
            $lockTimeout = $this->getLockTimeout();
            // Clean up locks that are older than lock timeout (default 5 minutes)
            $timeout = $this->dateTime->gmtDate('Y-m-d H:i:s', time() - $lockTimeout);
            
            $deleted = $connection->delete(
                $tableName,
                ['last_activity < ?' => $timeout]
            );
            
            return (int)$deleted;
        } catch (\Exception $e) {
            $this->logger->error('Error cleaning up expired locks: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get lock timeout from configuration
     *
     * @param int|null $storeId
     * @return int
     */
    public function getLockTimeout($storeId = null)
    {
        $timeout = (int) $this->scopeConfig->getValue(
            self::XML_PATH_LOCK_TIMEOUT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        
        return $timeout > 0 ? $timeout : self::DEFAULT_LOCK_TIMEOUT;
    }

}

