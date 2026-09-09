<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Controller\Cart;

use Dcw\ShareCart\Service\ShareCartLinkHandler;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Psr\Log\LoggerInterface;

class Apply implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ShareCartLinkHandler $shareCartLinkHandler,
        private readonly RedirectFactory $redirectFactory,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly MessageManager $messageManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute()
    {
        if ((string) $this->request->getParam('share_cart_apply') !== '1') {
            return $this->reject(
                (string) __('Invalid request. Please open the shared cart link from your email.'),
                'invalid_request_type'
            );
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            return $this->reject(
                (string) __('Your session has expired. Please open the shared cart link again.'),
                'invalid_form_key'
            );
        }

        $token = trim((string) $this->request->getParam('token'));

        if ($token === '') {
            return $this->reject(
                (string) __('Invalid shared cart link. Please open the link from your email again.'),
                'missing_token'
            );
        }

        try {
            $this->shareCartLinkHandler->processByToken($token);

            $this->messageManager->addSuccessMessage(
                (string) __('Shared cart items have been added to your cart.')
            );

            return $this->redirectFactory->create()->setPath('checkout/cart');
        } catch (LocalizedException $e) {
            return $this->reject($e->getMessage(), 'localized_exception');
        } catch (\Exception $e) {
            $this->logger->error('ShareCart apply failed', ['message' => $e->getMessage()]);

            return $this->reject(
                (string) __('Unable to load the shared cart. Please try again later.'),
                'unexpected_exception'
            );
        }
    }

    private function reject(string $message, string $reason)
    {
        $this->logger->warning('ShareCart apply rejected', [
            'reason' => $reason,
            'message' => $message,
        ]);

        $this->messageManager->addErrorMessage($message);

        return $this->redirectFactory->create()->setPath('checkout/cart');
    }
}
