<?php
/**
 * Controller to create a new quote from an expired quote
 */

namespace Dcw\RequestQuote\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Amasty\RequestQuote\Model\Quote\Session as QuoteSession;
use Amasty\RequestQuote\Model\QuoteRepository;
use Amasty\RequestQuote\Model\QuoteFactory;
use Amasty\RequestQuote\Model\Source\Status;
use Amasty\RequestQuote\Helper\Data as ConfigHelper;
use Amasty\RequestQuote\Helper\Date as DateHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\DateTime as DateTimeHelper;
use Magento\Framework\App\ResourceConnection;

class CreateNewFromExpired extends Action
{
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var QuoteSession
     */
    protected $quoteSession;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

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
     * @param Context $context
     * @param RedirectFactory $resultRedirectFactory
     * @param ResultFactory $resultFactory
     * @param QuoteSession $quoteSession
     * @param QuoteRepository $quoteRepository
     * @param QuoteFactory $quoteFactory
     * @param CustomerSession $customerSession
     * @param ConfigHelper $configHelper
     * @param DateHelper $dateHelper
     * @param StoreManagerInterface $storeManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param DateTimeHelper $dateTime
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Context $context,
        RedirectFactory $resultRedirectFactory,
        ResultFactory $resultFactory,
        QuoteSession $quoteSession,
        QuoteRepository $quoteRepository,
        QuoteFactory $quoteFactory,
        CustomerSession $customerSession,
        ConfigHelper $configHelper,
        DateHelper $dateHelper,
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepository,
        DateTimeHelper $dateTime,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->resultFactory = $resultFactory;
        $this->quoteSession = $quoteSession;
        $this->quoteRepository = $quoteRepository;
        $this->quoteFactory = $quoteFactory;
        $this->customerSession = $customerSession;
        $this->configHelper = $configHelper;
        $this->dateHelper = $dateHelper;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->dateTime = $dateTime;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Execute action to create new quote from expired quote
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $quoteId = (int) $this->getRequest()->getParam('quote_id');
        $isAjax = $this->getRequest()->isAjax() || $this->getRequest()->getParam('isAjax');

        if (!$quoteId) {
            $this->messageManager->addErrorMessage(__('Quote ID is required.'));
            if ($isAjax) {
                $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $result->setData(['redirectUrl' => $this->_url->getUrl('amasty_quote/account/index')]);
                return $result;
            }
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('amasty_quote/account/index');
            return $resultRedirect;
        }

        try {
            // Get the expired quote
            $expiredQuote = $this->getQuote($quoteId);

            if (!$expiredQuote || !$this->validateQuote($expiredQuote)) {
                throw new LocalizedException(__('You don\'t have permission to access this quote.'));
            }

            // Verify quote is expired
            if ($expiredQuote->getStatus() != Status::EXPIRED) {
                throw new LocalizedException(__('This quote is not expired. Only expired quotes can be used to create new quotes.'));
            }

            // Reload expired quote from repository to ensure fresh data with items
            $expiredQuote = $this->quoteRepository->get($expiredQuote->getId());
            $expiredQuote->getItemsCollection()->load();

            // Get current store
            $store = $this->storeManager->getStore();
            $storeId = $store->getId();
            $currencyCode = $store->getCurrentCurrencyCode();

            // Create a new quote by cloning the expired quote structure
            $newQuote = $this->quoteFactory->create();
            
            // Get customer ID - first from expired quote, fallback to logged-in customer
            $customerId = $expiredQuote->getCustomerId();
            if (!$customerId) {
                $customerId = $this->customerSession->getCustomerId();
            }
            
            // Copy basic quote data from expired quote (or use logged-in customer if expired quote has no customer)
            $newQuote->setStoreId($storeId);
            $newQuote->setCustomerId($customerId);
            
            // Load customer object once to get email, group ID, and name
            $customer = null;
            $customerName = '';
            $customerEmail = $expiredQuote->getCustomerEmail();
            $customerGroupId = $expiredQuote->getCustomerGroupId();
            
            if ($customerId) {
                try {
                    $customer = $this->customerRepository->getById($customerId);
                    if (!$customerEmail) {
                        $customerEmail = $customer->getEmail();
                    }
                    if (!$customerGroupId) {
                        $customerGroupId = $customer->getGroupId();
                    }
                    $customerName = trim($customer->getFirstname() . ' ' . $customer->getLastname());
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
            
            // Set currency code (fixes strtoupper() null error)
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
            // This follows the same pattern as InQuote.php to ensure all data is preserved
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

            // Save customer_name directly to amasty_quote table
            if ($customerName && $newQuote->getId()) {
                try {
                    $connection = $this->resourceConnection->getConnection();
                    $amastyQuoteTable = $connection->getTableName('amasty_quote');
                    $where = ['quote_id = ?' => $newQuote->getId()];
                    $connection->update($amastyQuoteTable, ['customer_name' => $customerName], $where);
                } catch (\Exception $e) {
                    // If direct update fails, log error but continue
                }
            }

            // Get increment_id from the quote
            $incrementId = $newQuote->getIncrementId() ?: '#' . $newQuote->getId();
            
            $this->messageManager->addSuccessMessage(
                __('New quote created successfully. Quote cart %1 has been added.', $incrementId)
            );
            
            if ($isAjax) {
                $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $result->setData(['redirectUrl' => $this->_url->getUrl('amasty_quote/account/index')]);
                return $result;
            }
            
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('amasty_quote/account/index');
            return $resultRedirect;
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            if ($isAjax) {
                $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $result->setData(['redirectUrl' => $this->_url->getUrl('amasty_quote/account/index')]);
                return $result;
            }
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('amasty_quote/account/index');
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to create new quote: %1', $e->getMessage()));
            if ($isAjax) {
                $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $result->setData(['redirectUrl' => $this->_url->getUrl('amasty_quote/account/index')]);
                return $result;
            }
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('amasty_quote/account/index');
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

    /**
     * Validate quote belongs to current customer
     *
     * @param \Amasty\RequestQuote\Api\Data\QuoteInterface $quote
     * @return bool
     */
    protected function validateQuote($quote)
    {
        if (!$quote->getCustomerId()) {
            return false;
        }

        return (int)$quote->getCustomerId() === (int)$this->customerSession->getCustomerId();
    }
}

