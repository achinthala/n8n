<?php

namespace Dcw\LineItemUpdateESD\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

/**
 * Admin controller to render Order ESD update form
 */
class Form extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @var string
     */
    public const ADMIN_RESOURCE = 'Dcw_LineItemUpdateESD::order_form';

    /**
     * Factory for creating backend result pages
     *
     * @var PageFactory
     */
    protected $resultPageFactory;


    /**
     * Constructor
     *
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Execute action to render the Order ESD update page
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $resultPage->setActiveMenu('Magento_Sales::sales');
        $resultPage->getConfig()->getTitle()->prepend(__('Update Order ESD'));

        return $resultPage;
    }
}
