<?php
/**
 * Admin controller to create a new quote from an expired quote
 */

namespace Dcw\RequestQuote\Controller\Adminhtml\Quote;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Amasty\RequestQuote\Model\QuoteRepository;
use Amasty\RequestQuote\Model\QuoteFactory;
use Amasty\RequestQuote\Model\Source\Status;
use Amasty\RequestQuote\Helper\Data as ConfigHelper;
use Amasty\RequestQuote\Helper\Date as DateHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\DateTime as DateTimeHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Backend\Model\Auth\Session as AdminSession;

class CreateNewFromExpired extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Amasty_RequestQuote::quote';

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var DateHelper
     */
    protected $dateHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var DateTimeHelper
     */
    protected $dateTime;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var AdminSession
     */
    protected $adminSession;

    /**
     * @param Context $context
     * @param RedirectFactory $resultRedirectFactory
     * @param ResultFactory $resultFactory
     * @param QuoteRepository $quoteRepository
     * @param QuoteFactory $quoteFactory
     * @param ConfigHelper $configHelper
     * @param DateHelper $dateHelper
     * @param StoreManagerInterface $storeManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param DateTimeHelper $dateTime
     * @param ResourceConnection $resourceConnection
     * @param AdminSession $adminSession
     */
    public function __construct(
        Context $context,
        RedirectFactory $resultRedirectFactory,
        ResultFactory $resultFactory,
        QuoteRepository $quoteRepository,
        QuoteFactory $quoteFactory,
        ConfigHelper $configHelper,
        DateHelper $dateHelper,
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepository,
        DateTimeHelper $dateTime,
        ResourceConnection $resourceConnection,
        AdminSession $adminSession
    ) {
        parent::__construct($context);
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->resultFactory = $resultFactory;
        $this->quoteRepository = $quoteRepository;
        $this->quoteFactory = $quoteFactory;
        $this->configHelper = $configHelper;
        $this->dateHelper = $dateHelper;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->dateTime = $dateTime;
        $this->resourceConnection = $resourceConnection;
        $this->adminSession = $adminSession;
    }

    /**
     * Execute action to create new quote from expired quote
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $quoteId = (int) $this->getRequest()->getParam('quote_id');

        if (!$quoteId) {
            $this->messageManager->addErrorMessage(__('Quote ID is required.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('amasty_quote/quote/index');
            return $resultRedirect;
        }

        try {
            // Get the expired quote
            $expiredQuote = $this->getQuote($quoteId);

            if (!$expiredQuote) {
                throw new LocalizedException(__('Quote not found.'));
            }

            // Verify quote is expired
            if ($expiredQuote->getStatus() != Status::EXPIRED) {
                throw new LocalizedException(__('This quote is not expired. Only expired quotes can be used to create new quotes.'));
            }

            // Reload expired quote from repository to ensure fresh data with items
            $expiredQuote = $this->quoteRepository->get($expiredQuote->getId());
            $expiredQuote->getItemsCollection()->load();

            // Get store from expired quote
            $storeId = $expiredQuote->getStoreId();
            if (!$storeId) {
                $store = $this->storeManager->getStore();
                $storeId = $store->getId();
            } else {
                $store = $this->storeManager->getStore($storeId);
            }
            $currencyCode = $store->getCurrentCurrencyCode();

            // Create a new quote by cloning the expired quote structure
            $newQuote = $this->quoteFactory->create();
            
            // Get customer ID from expired quote
            $customerId = $expiredQuote->getCustomerId();
            
            // Copy basic quote data from expired quote
            $newQuote->setStoreId($storeId);
            $newQuote->setCustomerId($customerId);
            
            // Load customer object once to get email, group ID, and name
            $customer = null;
            $customerName = '';
            $customerEmail = $expiredQuote->getCustomerEmail();
            $customerGroupId = $expiredQuote->getCustomerGroupId();
            
            // Try to get customer name from expired quote first (from amasty_quote table)
            if ($expiredQuote->getId()) {
                try {
                    $connection = $this->resourceConnection->getConnection();
                    $amastyQuoteTable = $connection->getTableName('amasty_quote');
                    $select = $connection->select()
                        ->from($amastyQuoteTable, ['customer_name'])
                        ->where('quote_id = ?', $expiredQuote->getId());
                    $result = $connection->fetchOne($select);
                    if ($result) {
                        $customerName = $result;
                    }
                } catch (\Exception $e) {
                    // If query fails, continue to try other methods
                }
            }
            
            if ($customerId) {
                try {
                    $customer = $this->customerRepository->getById($customerId);
                    if (!$customerEmail) {
                        $customerEmail = $customer->getEmail();
                    }
                    if (!$customerGroupId) {
                        $customerGroupId = $customer->getGroupId();
                    }
                    // Only set customer name if we don't have it already
                    if (!$customerName) {
                        $customerName = trim($customer->getFirstname() . ' ' . $customer->getLastname());
                    }
                } catch (\Exception $e) {
                    // If customer not found, continue with values from expired quote
                }
            }
            
            if ($customerEmail) {
                $newQuote->setCustomerEmail($customerEmail);
            }
            if ($customerGroupId) {
                $newQuote->setCustomerGroupId($customerGroupId);
            }
            
            // Set quote date (submited_date) - current GMT date
            $newQuote->setSubmitedDate($this->dateTime->gmtDate());
            
            $newQuote->setStatus(Status::APPROVED);
            $newQuote->setIsActive(false);
            
            // Set currency code
            $newQuote->setQuoteCurrencyCode($currencyCode);
            $newQuote->setBaseCurrencyCode($store->getBaseCurrencyCode());
            
            // Set relation to the expired quote (optional, for tracking)
            $newQuote->setRelationParentId($expiredQuote->getId());

            // Set expiration and reminder dates (similar to Approve controller)
            if ($expDays = $this->configHelper->getExpirationTime()) {
                $newQuote->setExpiredDate($this->dateHelper->increaseDays($expDays));
            }
            if ($remDays = $this->configHelper->getReminderTime()) {
                $newQuote->setReminderDate($this->dateHelper->increaseDays($remDays));
            }

            // Set store on quote (ensures currency and other store-specific data is set)
            $newQuote->setStore($store);

            // Save quote first to get an ID (needed for setting quoteId on items)
            $newQuote->setTotalsCollectedFlag(false);
            $this->quoteRepository->save($newQuote);

            // Copy items from expired quote to new quote using clone (preserves custom options)
            foreach ($expiredQuote->getAllVisibleItems() as $item) {
                // Clone the item - this preserves all data including custom options, buy request, etc.
                $newItem = clone $item;
                $newItem->setId(null); // Clear ID so it becomes a new item
                $newItem->setItemId(null);
                $newItem->setQuoteId($newQuote->getId()); // Set quote ID after quote is saved
                $newItem->setQuote($newQuote);
                $newItem->setIsObjectNew(true);
                $newItem->setParentItemId(null);
                
                // Add to new quote - addItem() handles all item data including options
                $newQuote->addItem($newItem);
                
                // Handle child items
                if ($item->getHasChildren()) {
                    foreach ($item->getChildren() as $child) {
                        $newChild = clone $child;
                        $newChild->setId(null);
                        $newChild->setItemId(null);
                        $newChild->setQuoteId($newQuote->getId());
                        $newChild->setQuote($newQuote);
                        $newChild->setIsObjectNew(true);
                        $newChild->setParentItemId(null);
                        $newChild->setParentItem($newItem);
                        $newQuote->addItem($newChild);
                    }
                }
            }

            // Collect totals and save after items are added
            $newQuote->setTotalsCollectedFlag(false);
            $newQuote->collectTotals();
            $this->quoteRepository->save($newQuote);

            // Reload quote from repository to ensure items are properly persisted
            $newQuote = $this->quoteRepository->get($newQuote->getId());
            $newQuote->getItemsCollection()->load();
            
            // Collect totals again on the reloaded quote to ensure they're calculated correctly
            $newQuote->setTotalsCollectedFlag(false);
            $newQuote->collectTotals();
            $this->quoteRepository->save($newQuote);

            // Get current admin user username for admin_user_assistance
            $adminUsername = '';
            if ($this->adminSession->isLoggedIn()) {
                $user = $this->adminSession->getUser();
                if ($user && $user->getId()) {
                    $adminUsername = $user->getUserName();
                }
            }

            // Save customer_name and admin_user_assistance
            if ($newQuote->getId()) {
                try {
                    $connection = $this->resourceConnection->getConnection();
                    
                    // Save customer_name to amasty_quote table
                    if ($customerName) {
                        $amastyQuoteTable = $connection->getTableName('amasty_quote');
                        $where = ['quote_id = ?' => $newQuote->getId()];
                        $connection->update($amastyQuoteTable, ['customer_name' => $customerName], $where);
                    }
                    
                    // Save admin_user_assistance to quote table
                    if ($adminUsername) {
                        $quoteTable = $connection->getTableName('quote');
                        $where = ['entity_id = ?' => $newQuote->getId()];
                        $connection->update($quoteTable, ['admin_user_assistance' => $adminUsername], $where);
                    }
                } catch (\Exception $e) {
                    // If direct update fails, log error but continue
                }
            }

            // Get increment_id from the quote
            $incrementId = $newQuote->getIncrementId() ?: '#' . $newQuote->getId();
            
            $this->messageManager->addSuccessMessage(
                __('New quote created successfully. Quote %1 has been created.', $incrementId)
            );
            
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('amasty_quote/quote/view', ['quote_id' => $newQuote->getId()]);
            return $resultRedirect;
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('amasty_quote/quote/view', ['quote_id' => $quoteId]);
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to create new quote: %1', $e->getMessage()));
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('amasty_quote/quote/view', ['quote_id' => $quoteId]);
            return $resultRedirect;
        }
    }

    /**
     * Get quote by ID
     *
     * @param int $quoteId
     * @return \Amasty\RequestQuote\Api\Data\QuoteInterface|null
     */
    protected function getQuote($quoteId)
    {
        try {
            $quote = $this->quoteRepository->get($quoteId);
            if ($quote->getId()) {
                return $quote;
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}

