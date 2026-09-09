<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Controller\Share;

use Dcw\ShareCart\Model\Config;
use Dcw\ShareCart\Service\ShareCartManager;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Submit implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly RequestInterface $request,
        private readonly ShareCartManager $shareCartManager,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        if (!$this->config->isEnabled()) {
            return $result->setData([
                'success' => false,
                'message' => (string) __('Share Cart is currently disabled.'),
            ]);
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            $this->logger->warning('ShareCart submit rejected: invalid form key');

            return $result->setData([
                'success' => false,
                'message' => (string) __('Invalid form key. Please refresh the page and try again.'),
            ]);
        }

        try {
            $this->validateInput();

            $shareData = [
                'your_name' => $this->request->getParam('your_name'),
                'recipient_name' => $this->request->getParam('recipient_name'),
                'recipient_email' => $this->request->getParam('recipient_email'),
                'recipient_phone' => $this->request->getParam('recipient_phone'),
                'sms_consent' => $this->request->getParam('sms_consent'),
                'custom_message' => $this->request->getParam('custom_message'),
            ];

            $shareCart = $this->shareCartManager->createShare($shareData);

            $email = (string) $shareCart->getRecipientEmail();

            return $result->setData([
                'success' => true,
                'message' => (string) __('Your cart was successfully shared with %1', $email),
                'recipient_email' => $email,
            ]);
        } catch (LocalizedException $e) {
            $this->logger->warning('ShareCart submit validation error: ' . $e->getMessage());

            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('ShareCart submit failed: ' . $e->getMessage());

            return $result->setData([
                'success' => false,
                'message' => (string) __('Unable to share your cart. Please try again later.'),
            ]);
        }
    }

    /**
     * @throws LocalizedException
     */
    private function validateInput(): void
    {
        $yourName = trim((string) $this->request->getParam('your_name'));
        $recipientName = trim((string) $this->request->getParam('recipient_name'));
        $recipientEmail = trim((string) $this->request->getParam('recipient_email'));
        $recipientPhone = trim((string) $this->request->getParam('recipient_phone'));

        if ($yourName === '') {
            throw new LocalizedException(__('Your name is required.'));
        }

        if ($recipientName === '') {
            throw new LocalizedException(__("Recipient's name is required."));
        }

        if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new LocalizedException(__("Please enter a valid recipient email address."));
        }

        if ($recipientPhone === '') {
            throw new LocalizedException(__("Recipient's phone number is required."));
        }
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
