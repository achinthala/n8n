<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Controller\Adminhtml\Validation;

use Dcw\ProductDataValidation\Model\JobManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Status extends Action
{
    public const ADMIN_RESOURCE = 'Dcw_ProductDataValidation::view';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly JobManager $jobManager,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $jobId = (int) $this->getRequest()->getParam('job_id');
        try {
            $job = $this->jobManager->getJob($jobId);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->resultRedirectFactory->create()->setPath('product_data_validation/validation/index');
        }

        $this->registry->register('dcw_pdv_current_job', $job);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dcw_ProductDataValidation::jobs');
        $resultPage->getConfig()->getTitle()->prepend(__('Validation Job #%1', $jobId));

        return $resultPage;
    }
}
