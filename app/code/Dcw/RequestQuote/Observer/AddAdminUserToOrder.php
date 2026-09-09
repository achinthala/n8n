<?php
namespace Dcw\RequestQuote\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Backend\Model\Auth\Session as AdminSession;

class AddAdminUserToOrder implements ObserverInterface
{
    protected $quoteRepository;
    protected $adminSession;
    protected $orderRepository;
    protected $resource;
    protected $_logger;

    public function __construct(
        CartRepositoryInterface $quoteRepository,
        OrderRepositoryInterface $orderRepository,
        AdminSession $adminSession,
        ResourceConnection $resource,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
        $this->adminSession = $adminSession;
        $this->resource = $resource;
        $this->_logger = $logger;
    }

    public function execute(Observer $observer)
    {
        // Get the order from the event
        $order = $observer->getEvent()->getOrder();
        
        // Get the quote_id from the order
        $quoteId = $order->getQuoteId();
        
        // Load the quote using the quote repository
        $quote = $this->quoteRepository->get($quoteId);

        // Check if the admin_user_assistance column exists in the quote
        $adminUserAssistance = $quote->getData('admin_user_assistance');
        
        if ($adminUserAssistance) {
            // Set the admin user assistance in the order
            $order->setAdminUserAssistance($adminUserAssistance);
            // Save the order to persist the changes
            $this->orderRepository->save($order);
        }

        return $this;
    }

}
