<?php
declare(strict_types=1);

namespace Dcw\ProductEnquiry\Controller\Index;

use Dcw\ProductEnquiry\Model\Config;
use Dcw\ProductEnquiry\Model\EnquiryManager;
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
        private readonly FormKeyValidator $formKeyValidator,
        private readonly Config $config,
        private readonly EnquiryManager $enquiryManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        if (!$this->config->isEnabled()) {
            return $result->setData([
                'success' => false,
                'message' => (string) __('Product enquiry is currently disabled.'),
            ]);
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            $this->logger->warning('ProductEnquiry submit rejected: invalid form key');

            return $result->setData([
                'success' => false,
                'message' => (string) __('Invalid form key. Please refresh the page and try again.'),
            ]);
        }

        try {
            $this->enquiryManager->createEnquiry([
                'name' => $this->request->getParam('name'),
                'email' => $this->request->getParam('email'),
                'phone' => $this->request->getParam('phone'),
                'message' => $this->request->getParam('message'),
                'product_id' => $this->request->getParam('product_id'),
            ]);

            return $result->setData([
                'success' => true,
                'message' => $this->config->getSuccessMessage(),
            ]);
        } catch (LocalizedException $e) {
            $this->logger->warning('ProductEnquiry submit validation error: ' . $e->getMessage());

            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('ProductEnquiry submit failed: ' . $e->getMessage());

            return $result->setData([
                'success' => false,
                'message' => (string) __('Unable to submit your enquiry. Please try again later.'),
            ]);
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
