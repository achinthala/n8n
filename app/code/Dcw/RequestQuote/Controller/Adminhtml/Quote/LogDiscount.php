<?php

declare(strict_types=1);

namespace Dcw\RequestQuote\Controller\Adminhtml\Quote;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Dcw\RequestQuote\Service\QuoteChangeLogger;
use Amasty\RequestQuote\Model\QuoteRepository;
use Magento\Framework\Exception\LocalizedException;

class LogDiscount extends Action
{
    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param QuoteChangeLogger $changeLogger
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly QuoteChangeLogger $changeLogger,
        private readonly QuoteRepository $quoteRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Log discount change (for button clicks - discount clearing happens in SavePlugin on save)
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        
        try {
            $quoteId = (int)$this->getRequest()->getParam('quote_id');
            $oldDiscount = (float)$this->getRequest()->getParam('old_discount', 0);
            $newDiscount = (float)$this->getRequest()->getParam('new_discount', 0);
            
            if (!$quoteId) {
                throw new LocalizedException(__('Quote ID is required.'));
            }
            
            // Verify quote exists
            $quote = $this->quoteRepository->get($quoteId);
            
            // Only log if discount actually changed
            // Note: Discount clearing happens in SavePlugin when Save Quote button is clicked
            if (abs($oldDiscount - $newDiscount) > 0.0001) {
                $this->changeLogger->logDiscountChanged(
                    $quoteId,
                    ['discount' => $oldDiscount],
                    ['discount' => $newDiscount]
                );
            }
            
            return $resultJson->setData([
                'success' => true,
                'message' => __('Discount change logged successfully.')
            ]);
            
        } catch (LocalizedException $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while logging discount change.')
            ]);
        }
    }

    /**
     * Check if user has access
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Amasty_RequestQuote::quote');
    }
}

