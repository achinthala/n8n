<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 */

namespace Dcw\RevenueRanking\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

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
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dcw_RevenueRanking::revenue_ranking');
        $resultPage->addBreadcrumb(__('Best Seller Control'), __('Best Seller Control'));
        $resultPage->addBreadcrumb(__('Revenue Ranking Management'), __('Revenue Ranking Management'));
        $resultPage->getConfig()->getTitle()->prepend(__('Revenue Ranking Management'));

        return $resultPage;
    }

    /**
     * Check if user has access to this controller
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dcw_RevenueRanking::revenue_ranking');
    }
}
