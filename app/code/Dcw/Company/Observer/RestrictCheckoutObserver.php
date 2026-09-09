<?php
namespace Dcw\Company\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;

class RestrictCheckoutObserver implements ObserverInterface
{
protected $checkoutSession;
protected $messageManager;
protected $productRepository;
/**
     * @var \Magento\Framework\UrlInterface
     */
private $url;

/**
     * @var \Magento\Framework\App\ResponseFactory
     */
private $responseFactory;

public function __construct (CheckoutSession $checkoutSession, MessageManager $messageManager, UrlInterface $url, ResponseFactory $responseFactory, ProductRepositoryInterface $productRepository)
{
$this->checkoutSession = $checkoutSession;
$this->messageManager = $messageManager;
$this->url = $url;
$this->responseFactory = $responseFactory;
$this->productRepository = $productRepository;
}

public function execute (\Magento\Framework\Event\Observer $observer)
{ 
$quote = $this->checkoutSession->getQuote();
foreach ($quote->getAllVisibleItems () as $item) {
$product = $this->productRepository->get($item->getSku());
if ($product->getIncstoresFreeProduct() == 1 &&  $product->getAttributeText('size') == 'NA') { 
// Throw an exception and redirect to cart page
//throw new \Magento\Framework\Exception\LocalizedException (__('You cannot proceed to checkout with this product. Please EDIT Free T-Shirt product in cart to add T-Shirt Size !'));
$this->messageManager->addErrorMessage (__('Please EDIT Free T-Shirt product in cart to add T-Shirt Size !'));
//return $this->redirectFactory->create()->setPath('checkout/cart');
$redirectionUrl = $this->url->getUrl('checkout/cart');
$this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();

return $this;
}
}
}
}