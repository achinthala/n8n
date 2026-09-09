<?php
declare(strict_types=1);

namespace Dcw\RequestQuote\Controller\Adminhtml\Quote\Edit;

use Amasty\RequestQuote\Controller\Adminhtml\Quote\Edit\LoadBlock as AmastyLoadBlock;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Action\Context;
use Magento\Backend\App\Action;
use Amasty\RequestQuote\Api\QuoteRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Dcw\RequestQuote\Service\QuoteLockService;
use Magento\Framework\Session\SessionManagerInterface;

class LoadBlock extends AmastyLoadBlock
{
 /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var \Amasty\RequestQuote\Model\Quote\Backend\Session
     */
    protected $quoteSession;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backendSession;

    /**
     * @var \Amasty\RequestQuote\Model\Quote\Backend\Edit
     */
    protected $quoteEditModel;

    /**
     * @var \Amasty\RequestQuote\Model\Email\Sender
     */
    protected $emailSender;

    /**
     * @var \Amasty\RequestQuote\Helper\Data
     */
    protected $configHelper;

    /**
     * @var \Amasty\RequestQuote\Helper\Date
     */
    protected $dateHelper;

    /**
     * @var \Amasty\RequestQuote\Model\Quote\Backend\FormDataProcessor
     */
    private $formDataProcessor;

    protected $resource;

    /**
     * @var QuoteLockService
     */
    protected $quoteLockService;

    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    public function __construct(
        Action\Context $context,
        \Amasty\RequestQuote\Model\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        QuoteRepositoryInterface $quoteRepository,
        \Amasty\RequestQuote\Model\Quote\Backend\Session $quoteSession,
        LoggerInterface $logger,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Framework\Escaper $escaper,
        ForwardFactory $resultForwardFactory,
        \Amasty\RequestQuote\Model\Quote\Backend\Edit $editModel,
        \Amasty\RequestQuote\Model\Email\Sender $emailSender,
        \Amasty\RequestQuote\Helper\Data $configHelper,
        \Amasty\RequestQuote\Helper\Date $dateHelper,
        \Amasty\RequestQuote\Model\Quote\Backend\FormDataProcessor $formDataProcessor,
        ResourceConnection $resource,
        QuoteLockService $quoteLockService,
        SessionManagerInterface $sessionManager
    ) {
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $translateInline,
            $resultPageFactory,
            $resultJsonFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $quoteRepository,
            $quoteSession,
            $logger,
            $productHelper,
            $escaper,
            $resultForwardFactory,
            $editModel,
            $emailSender,
            $configHelper,
            $dateHelper,
            $formDataProcessor
        );

        $productHelper->setSkipSaleableCheck(true);
        $this->escaper = $escaper;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->quoteSession = $quoteSession;
        $this->backendSession = $context->getSession();
        $this->quoteEditModel = $editModel;
        $this->emailSender = $emailSender;
        $this->configHelper = $configHelper;
        $this->dateHelper = $dateHelper;
        $this->formDataProcessor = $formDataProcessor;
        $this->resource = $resource;
        $this->quoteLockService = $quoteLockService;
        $this->sessionManager = $sessionManager;
    }

    public function execute()
    {
        $validationPassed = false;

        if ($quote = $this->initQuote(true)) {
            $quote->setForcedCurrency($quote->getQuoteCurrency());
            $validationPassed = true;
            
            // Acquire lock when admin opens the edit page
            // Use Amasty quote ID from request parameter (not Magento quote ID)
            try {
                $amastyQuoteId = (int)$this->getRequest()->getParam('quote_id');
                if ($amastyQuoteId) {
                    $sessionId = $this->sessionManager->getSessionId();
                    if (!$sessionId) {
                        // Fallback: use backend session ID if available
                        $sessionId = $this->backendSession->getSessionId();
                    }
                    // Acquire lock even if session ID is null (for admin, session ID might not be critical)
                    $this->quoteLockService->acquireLock($amastyQuoteId, $sessionId);
                }
            } catch (\Exception $e) {
                // Silent fail - don't break quote editing if lock fails
            }

            // Only process POST data if quote_id is present in POST
            // For AJAX block loading (like search_grid), quote_id might be in GET params, not POST
            // So we only process POST-specific logic if POST data exists
            if (isset($_POST['quote_id'])) {
                $quote = $this->loadOriginalQuote($_POST['quote_id']);
                $sessionQuote = $this->getQuote();

                try {
                    if (isset($_POST['reset_price_modificators']) && $_POST['reset_price_modificators'] === '1') {
                        //calling for reset price
                        $reloadQuote = $this->reloadQuote();
                        
                        // Check if the quote object is valid
                        if ($quote) {
                             // Get all items from the quote
                            $items = $quote->getAllItems();
                            $itemCount = count($items);

                            // Loop through the $_POST item array
                            if (isset($_POST['item']) && is_array($_POST['item'])) {
                                $postItems = $_POST['item'];
                                $postCount = count($postItems); // Count the number of items in $_POST

                                // Use the minimum count to avoid undefined index errors
                                $minCount = min($itemCount, $postCount);
                                
                                for ($i = 0; $i < $minCount; $i++) {
                                    // Get the current item ID from the POST data
                                    $currentItemId = array_keys($postItems)[$i]; // Get the key (item ID) based on the current index

                                    // Get the corresponding quote item using the same index
                                    if (isset($items[$i])) {
                                        $quoteItem = $items[$i]; 
                                        // Update the price in the $_POST array
                                        $_POST['item'][$currentItemId]['price'] = $quoteItem->getPrice();
                                       
                                        $sessionQuoteItems = $sessionQuote->getAllItems();
                                        foreach ($sessionQuoteItems as $sessionQuoteItem) {
                                            $quoteItemPrice = $this->getQuoteItemPrice($items,$sessionQuoteItem->getSku());
                                            
                                            if ($quoteItemPrice != 0) {
                                                $sessionQuoteItem->setPrice($quoteItemPrice); // Set new price
                                                $sessionQuoteItem->setCustomPrice($quoteItemPrice); 
                                                $sessionQuoteItem->setBasePrice($quoteItemPrice); 
                                                $sessionQuoteItem->setOriginalQuoteItemPrice($quoteItemPrice); 
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        //unset the reset button
                        unset($_POST['reset_price_modificators']);
                        unset($_POST['quote']['surcharge']);
                
                        $this->resetDiscountOvercharge($_POST['quote_id']);
                       // $this->initSession()->processActionData();
                    } else {
                        $this->initSession()->processActionData();
                    }
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->reloadQuote();
                    $this->messageManager->addErrorMessage($e->getMessage());
                } catch (\Exception $e) {
                    $this->reloadQuote();
                    $this->messageManager->addExceptionMessage($e, $e->getMessage());
                }
            } else {
                // For AJAX requests without POST data (like search_grid), still process session data if needed
                // But don't break the request - let it continue to load blocks
                try {
                    $this->initSession()->processActionData();
                } catch (\Exception $e) {
                    // Silent fail for AJAX block loading
                }
            }
        }

        $request = $this->getRequest();
        $asJson = $request->getParam('json');
        $block = $request->getParam('block');

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        if ($asJson) {
            $resultPage->addHandle('amasty_quote_load_block_json');
        } else {
            $resultPage->addHandle('amasty_quote_load_block_plain');
        }

        if ($validationPassed) {
            if ($block) {
                $blocks = explode(',', $block);
                if ($asJson && !in_array('message', $blocks)) {
                    $blocks[] = 'message';
                }

                foreach ($blocks as $block) {
                    $resultPage->addHandle('amasty_quote_load_block_' . $block);
                }
            }
        } else {
            $layout = $resultPage->getLayout();
            $ajaxRedirect = $layout->createBlock(
                \Amasty\RequestQuote\Block\Adminhtml\Quote\Edit\Validation\Redirect::class,
                'ajaxRedirect'
            );
            $ajaxRedirect->setRedirectUrl($this->getUrl(
                'amasty_quote/quote/view',
                ['quote_id' => $this->getRequest()->getParam('quote_id')]
            ));
            $ajaxExpired = $layout->createBlock(
                \Amasty\RequestQuote\Block\Adminhtml\Quote\Edit\Validation\Expired::class,
                'ajaxExpired'
            );
            $contentBlock = $layout->getBlock('content');
            if ($contentBlock) {
                $contentBlock->setChild('ajaxRedirect', $ajaxRedirect);
                $contentBlock->setChild('ajaxExpired', $ajaxExpired);
            } else {
                $this->logger->error('RequestQuote LoadBlock: missing content block for validation result rendering.');
            }
        }

        $result = $resultPage->getLayout()->renderElement('content');

        if ($request->getParam('as_js_varname')) {
            $this->backendSession->setUpdateResult($result);
            return $this->resultRedirectFactory->create()->setPath('amasty_quote/*/showUpdateResult');
        }
        
        return $this->resultRawFactory->create()->setContents($result);
    }

    public function createLog($msg)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/RequestQuoteLoadBlock.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }

    public function resetDiscountOvercharge($quoteId)
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('amasty_quote');
       
        // Prepare the data to update
        $data = ['discount' => 0, 'surcharge' => 0];
        $where = ['quote_id = ?' => $quoteId];

        // Execute the update query
        $connection->update($tableName, $data, $where);
    }

    public function getQuoteItemPrice($items,$sku)
    {
        foreach($items as $item) {
            if ($item->getSku() == $sku) {
                $originalPrice = $item->getData('original_quote_item_price');

                if (!empty($originalPrice)) {
                    return $originalPrice;
                }

                if ($item->getPrice()!=0) {
                    return $item->getPrice();
                }
            }
        }

        return 0;
    }

    public function loadOriginalQuote($quoteId)
    {
        try {
            $quote = $this->quoteRepository->get($quoteId);
           
            $prepareData = [];
            //clone the price
            // Iterate through each item in the quote
            foreach ($quote->getAllItems() as $quoteItem) {
                // Copy the original_custom_price to original_quote_item_price
                if (!$quoteItem->getData('original_quote_item_price')) {
                    
                    if ($quoteItem->getData('custom_price')) {
                        // If custom_price is set, use it
                        $customPrice = $quoteItem->getData('custom_price');
                        $prepareData[] = $customPrice;
                        $quoteItem->setData('original_quote_item_price', $customPrice);
                    } elseif ($quoteItem->getData('base_price')) {
                        // If custom_price is not set, fall back to base_price
                        $basePrice = $quoteItem->getData('base_price');
                        
                        $prepareData[] = $basePrice;
                        $quoteItem->setData('original_quote_item_price', $basePrice);
                    }
                }
            }
 
            if (!$quote->getOriginalQuoteItemPrice()) { 
                $implodeData = implode(',',$prepareData);
                $quote->setOriginalQuoteItemPrice($implodeData);
            }
            // Save the changes to quote items
            $this->quoteRepository->save($quote);

            return $quote;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
