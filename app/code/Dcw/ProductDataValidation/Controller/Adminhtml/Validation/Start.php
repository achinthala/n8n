<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Controller\Adminhtml\Validation;

use Dcw\ProductDataValidation\Model\JobManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;

/**
 * Admin action: queue a validation job (confirmation handled in UI).
 */
class Start extends Action
{
    public const ADMIN_RESOURCE = 'Dcw_ProductDataValidation::run';

    public function __construct(
        Context $context,
        private readonly JobManager $jobManager,
        private readonly AuthSession $authSession
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $adminUserId = (int) $this->authSession->getUser()?->getId();
            $job = $this->jobManager->createPendingJob($adminUserId ?: null);
            $this->messageManager->addSuccessMessage(
                __(
                    'Product data validation has been queued successfully. Job ID: #%1. '
                    . 'The report will be available once the validation process is completed.',
                    $job->getJobId()
                )
            );
            return $resultRedirect->setPath('product_data_validation/validation/status', [
                'job_id' => $job->getJobId(),
            ]);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(
                __('Unable to queue product data validation. Please try again or check the logs.')
            );
        }

        return $resultRedirect->setPath('product_data_validation/validation/index');
    }
}
