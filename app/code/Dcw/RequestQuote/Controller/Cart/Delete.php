<?php
/**
 * Override Amasty RequestQuote Cart Delete controller to ensure item removal works correctly
 */

namespace Dcw\RequestQuote\Controller\Cart;

use Amasty\RequestQuote\Controller\Cart\Delete as AmastyDelete;
use Amasty\RequestQuote\Model\QuoteRepository;
use Dcw\RequestQuote\Service\QuoteChangeLogger;
use Dcw\RequestQuote\Service\DiscountThresholdService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Amasty\RequestQuote\Model\Quote\Session as AmastyQuoteSession;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Amasty\RequestQuote\Model\Cart as AmastyCart;
use Magento\Framework\Locale\ResolverInterface as LocaleResolver;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Json\EncoderInterface;
use Amasty\RequestQuote\Helper\Cart as AmastyCartHelper;
use Magento\Framework\DataObjectFactory;
use Amasty\RequestQuote\Model\Email\Sender;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Amasty\RequestQuote\Model\Email\AdminNotification;
use Magento\Customer\Api\AccountManagementInterface as CustomerAccountManagement;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Amasty\RequestQuote\Model\HidePrice\Provider as HidePriceProvider;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\CustomerExtractor;
use Amasty\RequestQuote\Model\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Amasty\RequestQuote\Model\UrlResolver;
use Magento\Framework\Filter\LocalizedToNormalized;
use Amasty\RequestQuote\Model\ConfigProvider;
use Amasty\RequestQuote\Api\AccountManagementInterface as QuoteAccountManagement;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\ResourceConnection;

class Delete extends AmastyDelete
{
    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var QuoteChangeLogger
     */
    protected $quoteChangeLogger;

    /**
     * @var DiscountThresholdService
     */
    protected $discountThresholdService;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param AmastyQuoteSession $amastyQuoteSession
     * @param StoreManagerInterface $storeManager
     * @param FormKeyValidator $formKeyValidator
     * @param AmastyCart $amastyCart
     * @param LocaleResolver $localeResolver
     * @param PageFactory $resultPageFactory
     * @param EncoderInterface $encoder
     * @param AmastyCartHelper $cartHelper
     * @param DataObjectFactory $dataObjectFactory
     * @param Sender $emailSender
     * @param SessionFactory $customerSessionFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Amasty\RequestQuote\Helper\Data $configHelper
     * @param AdminNotification $adminNotification
     * @param CustomerAccountManagement $accountManagement
     * @param CustomerUrl $customerUrl
     * @param AuthenticationInterface $authentication
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param PhpCookieManager $cookieManager
     * @param HidePriceProvider $hidePriceProvider
     * @param TimezoneInterface $timezone
     * @param CustomerExtractor $customerExtractor
     * @param \Psr\Log\LoggerInterface $logger
     * @param Registry $registry
     * @param DateTime $dateTime
     * @param UrlResolver $urlResolver
     * @param QuoteRepository $quoteRepository
     * @param QuoteChangeLogger $quoteChangeLogger
     * @param DiscountThresholdService $discountThresholdService
     * @param ResultFactory $resultFactory
     * @param ResourceConnection $resourceConnection
     * @param LocalizedToNormalized|null $localizedToNormalized
     * @param ConfigProvider|null $configProvider
     * @param QuoteAccountManagement|null $quoteAccountManagement
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        ScopeConfigInterface $scopeConfig,
        AmastyQuoteSession $amastyQuoteSession,
        StoreManagerInterface $storeManager,
        FormKeyValidator $formKeyValidator,
        AmastyCart $amastyCart,
        LocaleResolver $localeResolver,
        PageFactory $resultPageFactory,
        EncoderInterface $encoder,
        AmastyCartHelper $cartHelper,
        DataObjectFactory $dataObjectFactory,
        Sender $emailSender,
        SessionFactory $customerSessionFactory,
        PriceCurrencyInterface $priceCurrency,
        \Amasty\RequestQuote\Helper\Data $configHelper,
        AdminNotification $adminNotification,
        CustomerAccountManagement $accountManagement,
        CustomerUrl $customerUrl,
        AuthenticationInterface $authentication,
        CookieMetadataFactory $cookieMetadataFactory,
        PhpCookieManager $cookieManager,
        HidePriceProvider $hidePriceProvider,
        TimezoneInterface $timezone,
        CustomerExtractor $customerExtractor,
        LoggerInterface $logger,
        Registry $registry,
        DateTime $dateTime,
        UrlResolver $urlResolver,
        QuoteRepository $quoteRepository,
        QuoteChangeLogger $quoteChangeLogger,
        DiscountThresholdService $discountThresholdService,
        ResultFactory $resultFactory,
        ResourceConnection $resourceConnection,
        ?LocalizedToNormalized $localizedToNormalized = null,
        ?ConfigProvider $configProvider = null,
        ?QuoteAccountManagement $quoteAccountManagement = null,
        array $data = []
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteChangeLogger = $quoteChangeLogger;
        $this->discountThresholdService = $discountThresholdService;
        $this->resultFactory = $resultFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct(
            $context,
            $scopeConfig,
            $amastyQuoteSession,
            $storeManager,
            $formKeyValidator,
            $amastyCart,
            $localeResolver,
            $resultPageFactory,
            $encoder,
            $cartHelper,
            $dataObjectFactory,
            $emailSender,
            $customerSessionFactory,
            $priceCurrency,
            $configHelper,
            $adminNotification,
            $accountManagement,
            $customerUrl,
            $authentication,
            $cookieMetadataFactory,
            $cookieManager,
            $hidePriceProvider,
            $timezone,
            $customerExtractor,
            $logger,
            $registry,
            $dateTime,
            $urlResolver,
            $localizedToNormalized,
            $configProvider,
            $quoteAccountManagement
        );
    }
    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $id = (int)$this->getRequest()->getParam('id');
        $isAjax = $this->getRequest()->isAjax() ||
            $this->getRequest()->getHeader('X-Requested-With') === 'XMLHttpRequest';

        if ($id) {
            try {
                $quote = $this->cart->getQuote();
                
                // Try to find item by entity ID first
                $item = $quote->getItemById($id);
                
                // If not found, try to find by item_id
                if (!$item) {
                    foreach ($quote->getAllItems() as $quoteItem) {
                        if ($quoteItem->getItemId() == $id) {
                            $item = $quoteItem;
                            $id = $item->getId(); // Use entity ID instead
                            break;
                        }
                    }
                }
                
                if ($item) {
                    $quoteId = (int)$quote->getId();

                    // Quote has discount + user removing item = always 100% reduction, show popup
                    if ($quoteId) {
                        try {
                            $isAmastyQuote = $this->quoteRepository->isAmastyQuote($quoteId);

                            if ($isAmastyQuote) {
                                $hasDiscount = $this->discountThresholdService->hasDiscount($quoteId);

                                if ($hasDiscount) {
                                    if ($isAjax) {
                                        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                                        return $resultJson->setData([
                                            'show_discount_removal_popup' => true,
                                            'message' => __('You have reduced the quantity of items in a discounted quote beyond the allowed limit. Your discount will be removed if you proceed. Do you want to continue?'),
                                            'quote_id' => $quoteId,
                                            'item_id' => (int)$item->getId()
                                        ]);
                                    }
                                    throw new LocalizedException(
                                        __('Discount quantity reduction threshold over the limit. You cannot make changes on this quote. Please contact the admin.')
                                    );
                                }
                            }
                        } catch (LocalizedException $e) {
                            // Check if request is AJAX
                            $isAjax = $this->getRequest()->isAjax() || 
                                     $this->getRequest()->getHeader('X-Requested-With') === 'XMLHttpRequest';
                            
                            if ($isAjax) {
                                // Return JSON response for AJAX requests
                                $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                                $resultJson->setData([
                                    'success' => false,
                                    'errors' => true,
                                    'message' => $e->getMessage()
                                ]);
                                return $resultJson;
                            } else {
                                // Return redirect for non-AJAX requests
                                $this->messageManager->addErrorMessage($e->getMessage());
                                $defaultUrl = $this->_url->getUrl('*/*');
                                return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRedirectUrl($defaultUrl));
                            }
                        } catch (\Exception $e) {
                            // Continue if validation fails (don't block removal)
                        }
                    }
                    
                    // Get item data before removal for logging
                    $itemData = [
                        'item_id' => $item->getId(),
                        'sku' => $item->getSku(),
                        'name' => $item->getName(),
                        'qty' => $item->getQty(),
                        'price' => $item->getPrice(),
                        'row_total' => $item->getRowTotal(),
                    ];
                    
                    // Remove the item
                    $this->cart->removeItem($id);
                    
                    // Ensure item is marked as deleted (fix for removal issue)
                    if (!$item->isDeleted()) {
                        $item->isDeleted(true);
                    }
                    
                    // Save the cart
                    $this->cart->save();

                    // Log item removal (only for Amasty quotes)
                    if ($quoteId) {
                        try {
                            if ($this->quoteRepository->isAmastyQuote($quoteId)) {
                                $this->quoteChangeLogger->logItemRemoved($quoteId, (int)$item->getId(), $itemData);
                                
                                // Check if all items are removed - if so, clear discount and admin_note
                                $quote = $this->cart->getQuote();
                                $allItems = $quote->getAllVisibleItems();
                                
                                if (empty($allItems)) {
                                    $this->clearDiscountAndRemarks($quoteId);
                                }
                            }
                        } catch (\Exception $e) {
                            // Silent fail - don't break quote operations if logging fails
                        }
                    }
                } else {
                    $this->messageManager->addError(__('Item not found in quote.'));
                }
            } catch (\Exception $e) {
                $this->messageManager->addError(__('We can\'t remove the item.'));
            }
        }
        $defaultUrl = $this->_url->getUrl('*/*');
        return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRedirectUrl($defaultUrl));
    }

    /**
     * Clear discount and remove admin_note from remarks in amasty_quote table
     *
     * @param int $quoteId
     * @return void
     */
    private function clearDiscountAndRemarks(int $quoteId): void
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('amasty_quote');
            
            // Get current remarks
            $select = $connection->select()
                ->from($tableName, ['remarks'])
                ->where('quote_id = ?', $quoteId)
                ->limit(1);
            
            $remarksJson = $connection->fetchOne($select);
            
            // Prepare update data
            $data = ['discount' => 0];
            
            // Clear admin_note from remarks if it exists
            if ($remarksJson) {
                $remarks = json_decode($remarksJson, true);
                if (is_array($remarks) && isset($remarks['admin_note'])) {
                    // Remove admin_note from remarks
                    unset($remarks['admin_note']);
                    
                    // If remarks is now empty or only has empty values, set to null
                    if (empty($remarks) || (count($remarks) === 1 && isset($remarks['customer_note']) && empty($remarks['customer_note']))) {
                        $data['remarks'] = null;
                    } else {
                        // Re-encode remarks without admin_note
                        $data['remarks'] = json_encode($remarks);
                    }
                }
            }
            
            // Update amasty_quote table
            $where = ['quote_id = ?' => $quoteId];
            $connection->update($tableName, $data, $where);
            
        } catch (\Exception $e) {
            // Continue silently - quote operations should still succeed
        }
    }
}

