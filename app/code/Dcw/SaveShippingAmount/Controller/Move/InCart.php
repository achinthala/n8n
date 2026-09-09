<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Request a Quote Base for Magento 2
 */

namespace Dcw\SaveShippingAmount\Controller\Move;

use Dcw\SaveShippingAmount\Service\MoveToCartService;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect as ResultRedirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Exception\LocalizedException;

class InCart implements HttpPostActionInterface
{
    public function __construct(
        private readonly ResultFactory $resultFactory,
        private readonly RequestInterface $request,
        private readonly MoveToCartService $moveToCartService,
        private readonly MessageManagerInterface $messageManager
    ) {
    }

    public function execute(): ResultRedirect
    {
        /** @var ResultRedirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $quoteId = (int) $this->request->getParam('quote_id');
        if (!$quoteId) {
            $this->messageManager->addErrorMessage(__('Quote id not passed.'));
            $resultRedirect->setRefererUrl();
            return $resultRedirect;
        }

        try {
            $redirectToCheckout = (strpos($this->request->getParam('redirect_url', ''), 'checkout') !== false
                && strpos($this->request->getParam('redirect_url', ''), 'cart') === false);
            $this->moveToCartService->execute($quoteId, $redirectToCheckout);
            $redirectPath = $this->request->getParam('redirect_url', 'checkout/cart');
            $resultRedirect->setPath($redirectPath);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect->setRefererUrl();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to move quote to cart. Please try again.'));
            $resultRedirect->setRefererUrl();
        }

        return $resultRedirect;
    }
}
