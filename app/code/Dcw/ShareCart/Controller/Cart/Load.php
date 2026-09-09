<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Controller\Cart;

use Dcw\ShareCart\Helper\RequestContext;
use Dcw\ShareCart\ViewModel\ShareCartRestore;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlInterface;

class Load implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RedirectFactory $redirectFactory,
        private readonly ResultFactory $resultFactory,
        private readonly UrlInterface $urlBuilder,
        private readonly RequestContext $requestContext
    ) {
    }

    public function execute()
    {
        $cartUrl = $this->urlBuilder->getUrl('checkout/cart');
        $token = trim((string) $this->request->getParam('token'));

        if ($this->requestContext->isLinkPrefetchRequest()) {
            return $this->redirectFactory->create()->setPath('checkout/cart');
        }

        if ($token !== '') {
            return $this->redirectFactory->create()->setUrl(
                $cartUrl . '#' . ShareCartRestore::HASH_TOKEN_PARAM . '=' . rawurlencode($token)
            );
        }

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">'
            . '<meta http-equiv="Cache-Control" content="no-store">'
            . '<script>location.replace(' . json_encode($cartUrl) . ' + location.hash);</script>'
            . '</head><body></body></html>';

        return $this->resultFactory->create(ResultFactory::TYPE_RAW)
            ->setHeader('Content-Type', 'text/html; charset=UTF-8', true)
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true)
            ->setContents($html);
    }
}
