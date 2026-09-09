<?php
/**
 * Controller to load a quote into the quote cart for editing
 */

namespace Dcw\RequestQuote\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Amasty\RequestQuote\Model\Quote\Session as QuoteSession;
use Amasty\RequestQuote\Model\QuoteRepository;
use Amasty\RequestQuote\Model\UrlResolver;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Dcw\RequestQuote\Model\EditQuoteProcessor;
use Dcw\RequestQuote\ViewModel\ActiveCartIcon;
use Dcw\RequestQuote\Model\ValidateQuoteStatus;
use Dcw\RequestQuote\Service\QuoteLockService;
use Dcw\RequestQuote\ViewModel\Data as RequestQuoteViewModel;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManagerInterface;

class LoadQuote extends Action
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
     * @var UrlResolver
     */
    protected $urlResolver;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var EditQuoteProcessor
     */
    protected $editQuoteProcessor;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var ValidateQuoteStatus
     */
    protected $validateQuoteStatus;

    /**
     * @var QuoteLockService
     */
    protected $quoteLockService;

    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var RequestQuoteViewModel
     */
    protected $requestQuoteViewModel;

    /**
     * @param Context $context
     * @param RedirectFactory $resultRedirectFactory
     * @param ResultFactory $resultFactory
     * @param QuoteSession $quoteSession
     * @param QuoteRepository $quoteRepository
     * @param UrlResolver $urlResolver
     * @param CustomerSession $customerSession
     * @param EditQuoteProcessor $editQuoteProcessor
     * @param ValidateQuoteStatus $validateQuoteStatus
     * @param QuoteLockService $quoteLockService
     * @param SessionManagerInterface $sessionManager
     * @param CheckoutSession $checkoutSession
     * @param RequestQuoteViewModel $requestQuoteViewModel
     */
    public function __construct(
        Context $context,
        RedirectFactory $resultRedirectFactory,
        ResultFactory $resultFactory,
        QuoteSession $quoteSession,
        QuoteRepository $quoteRepository,
        UrlResolver $urlResolver,
        CustomerSession $customerSession,
        EditQuoteProcessor $editQuoteProcessor,
        ValidateQuoteStatus $validateQuoteStatus,
        QuoteLockService $quoteLockService,
        SessionManagerInterface $sessionManager,
        CheckoutSession $checkoutSession,
        RequestQuoteViewModel $requestQuoteViewModel
    ) {
        parent::__construct($context);
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->resultFactory = $resultFactory;
        $this->quoteSession = $quoteSession;
        $this->quoteRepository = $quoteRepository;
        $this->urlResolver = $urlResolver;
        $this->customerSession = $customerSession;
        $this->editQuoteProcessor = $editQuoteProcessor;
        $this->validateQuoteStatus = $validateQuoteStatus;
        $this->quoteLockService = $quoteLockService;
        $this->sessionManager = $sessionManager;
        $this->checkoutSession = $checkoutSession;
        $this->requestQuoteViewModel = $requestQuoteViewModel;
    }

    /**
     * Execute action to load quote into cart
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
            $targetQuote = $this->getQuote($quoteId);

            if (!$targetQuote || !$this->validateQuote($targetQuote)) {
                throw new LocalizedException(__('You don\'t have permission to edit this quote.'));
            }

            if (!$this->validateQuoteStatus->validate($targetQuote)) {
                throw new LocalizedException(
                    __('This quote cannot be edited. Quote status: %1. Please check system configuration for allowed edit statuses.', 
                    $targetQuote->getStatus())
                );
            }

            $sessionId = $this->sessionManager->getSessionId();
            $lockStatus = $this->quoteLockService->getLockStatus($quoteId, $sessionId);
            
            if ($lockStatus && $lockStatus['is_locked'] && isset($lockStatus['locked_by_type']) && $lockStatus['locked_by_type'] === 'admin') {
                $adminName = $lockStatus['locked_by_name'] ?? 'Admin';
                throw new LocalizedException(
                    __('This quote is currently being edited by %1. Please try again later.', $adminName)
                );
            }

            try {
                $this->quoteLockService->acquireLock($quoteId, $sessionId);
            } catch (\Exception $e) {
                $lockStatus = $this->quoteLockService->getLockStatus($quoteId, $sessionId);
                if ($lockStatus && $lockStatus['is_locked'] && isset($lockStatus['locked_by_type']) && $lockStatus['locked_by_type'] === 'admin') {
                    $adminName = $lockStatus['locked_by_name'] ?? 'Admin';
                    throw new LocalizedException(
                        __('This quote is currently being edited by %1. Please try again later.', $adminName)
                    );
                }
            }

            $currentQuote = $this->quoteSession->getQuote();
            $result = $this->editQuoteProcessor->execute($targetQuote, $currentQuote);

            $targetQuote->getItemsCollection()->load();
            $hasPriceChanges = $this->requestQuoteViewModel->hasQuotePriceChanges($targetQuote);

            $previousQuoteKey = $this->sessionManager->getData('last_price_check_quote_id');
            if ($previousQuoteKey && $previousQuoteKey != $quoteId) {
                $this->sessionManager->unsetData('price_change_notice_shown_cart_' . $previousQuoteKey);
            }
            
            if ($result['hadPreviousItems']) {
                $this->sessionManager->unsetData('price_change_notice_shown_cart_' . $quoteId);
            }
            
            $this->sessionManager->setData('last_price_check_quote_id', $quoteId);
            // Show quote cart icon, hide regular cart icon when editing quote
            $this->checkoutSession->setData(ActiveCartIcon::getSessionKey(), ActiveCartIcon::getTypeQuote());
            $cartUrl = $this->urlResolver->getCartUrl();
            
            if ($result['hadPreviousItems']) {
                $this->messageManager->addNoticeMessage(
                    __('You were editing another quote. The previous quote items have been cleared and replaced with the new quote items. You can now edit this quote.')
                );
            } else {
                $this->messageManager->addSuccessMessage(__('Quote loaded successfully. You can now edit it.'));
            }
            
            if ($isAjax) {
                $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $result->setData(['redirectUrl' => $cartUrl]);
                return $result;
            }
            
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($cartUrl);
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
            $this->messageManager->addErrorMessage(__('Unable to load quote: %1', $e->getMessage()));
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
     * @param \Amasty\RequestQuote\Api\Data\QuoteInterface $targetQuote
     * @return bool
     */
    protected function validateQuote($targetQuote)
    {
        if (!$targetQuote->getCustomerId()) {
            return false;
        }

        return (int)$targetQuote->getCustomerId() === (int)$this->customerSession->getCustomerId();
    }
}

