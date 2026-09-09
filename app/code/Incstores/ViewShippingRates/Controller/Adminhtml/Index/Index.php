<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Incstores_ViewShippingRates::shipping_rates';

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Execute action based on request and return result
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Incstores_ViewShippingRates::shipping_rates');
        $resultPage->addBreadcrumb(__('Sales'), __('Sales'));
        $resultPage->addBreadcrumb(__('Operations'), __('Operations'));
        $resultPage->addBreadcrumb(__('Shipping Rates'), __('Shipping Rates'));
        $resultPage->getConfig()->getTitle()->prepend(__('Shipping Rates'));

        // Pass URL parameters to block for auto-initialization
        $block = $resultPage->getLayout()->getBlock('shipping_rates_form');
        if ($block) {
            $quoteId = $this->getRequest()->getParam('quoteId');
            $orderId = $this->getRequest()->getParam('orderId');
            
            if ($quoteId) {
                $block->setData('auto_quote_id', $quoteId);
            }
            if ($orderId) {
                $block->setData('auto_order_id', $orderId);
            }
        }

        return $resultPage;
    }
}