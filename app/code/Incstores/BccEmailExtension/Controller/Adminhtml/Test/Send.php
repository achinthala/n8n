<?php
/**
 * TEST FILE - Created for testing BccEmailExtension - Safe to delete
 * Controller that handles the email sending and displays results
 */

namespace Incstores\BccEmailExtension\Controller\Adminhtml\Test;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

class Send extends Action
{
    protected $transportBuilder;
    protected $logger;

    public function __construct(
        Context $context,
        TransportBuilder $transportBuilder,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->transportBuilder = $transportBuilder;
        $this->logger = $logger;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/index');

        if (!$this->getRequest()->isPost()) {
            $this->messageManager->addErrorMessage(__('Invalid request method.'));
            return $resultRedirect;
        }

        $toEmail = $this->getRequest()->getParam('to_email');
        $message = $this->getRequest()->getParam('message');

        // Validate inputs
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $this->messageManager->addErrorMessage(__('Please provide a valid email address.'));
            return $resultRedirect;
        }

        if (empty($message)) {
            $this->messageManager->addErrorMessage(__('Please provide a test message.'));
            return $resultRedirect;
        }

        try {
            $this->logger->info('BCC Email Test: Starting email build', [
                'to_email' => $toEmail,
                'message_preview' => substr($message, 0, 100),
            ]);

            // Build and send email using Magento's standard mail system
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('bccemailtest_test_template')
                ->setTemplateOptions([
                    'area' => Area::AREA_ADMINHTML,
                    'store' => Store::DEFAULT_STORE_ID,
                ])
                ->setTemplateVars([
                    'message' => $message,
                    'to_email' => $toEmail,
                ])
                ->setFromByScope([
                    'name' => 'BCC Email Test (TEST)',
                    'email' => 'test@flooringinc.com',
                ])
                ->addTo($toEmail)
                ->getTransport();

            $this->logger->info('BCC Email Test: Transport built, about to send');

            $transport->sendMessage();

            $this->logger->info('BCC Email Test: sendMessage() completed');

            $this->messageManager->addSuccessMessage(
                __('Test email sent successfully to %1. Check MailHog at http://localhost:8025/', $toEmail)
            );

            $this->logger->info('BCC Email Test: Email sent successfully', [
                'to_email' => $toEmail,
                'message_preview' => substr($message, 0, 100),
                'mailhog_url' => 'http://localhost:8025/',
            ]);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->messageManager->addErrorMessage(
                __('Failed to send test email: %1', $errorMessage)
            );

            $this->logger->error('BCC Email Test: Failed to send email', [
                'to_email' => $toEmail,
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $resultRedirect;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Backend::admin');
    }
}
