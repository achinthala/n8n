<?php
namespace Dcw\Ordersaveadmin\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Backend\Model\Auth\Session;
use Magento\User\Model\UserFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\ResourceConnection;


class OrderPlaceAfterObserver implements ObserverInterface
{
    protected $session;
    protected $userFactory;
    protected $orderRepository;
	private ResourceConnection $resource;

    public function __construct(
        \Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminIdInterface,
        Session $session,
        UserFactory $userFactory,
        OrderRepositoryInterface $orderRepository,
		ResourceConnection $resource
    ) {
        
        $this->getLoggedAsCustomerAdminIdInterface = $getLoggedAsCustomerAdminIdInterface;
        $this->session = $session;
        $this->userFactory = $userFactory;
        $this->orderRepository = $orderRepository;
		$this->resource = $resource;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
		$this->createLogs($order->getIncrementId().' Shipping Amount: ' . $order->getShippingAmount());
        $this->createLog("init observer");
        $admin_username = $this->getAdminIdLoggedAsCustomer();
        $this->createLog($admin_username);
		
		$connection = $this->resource->getConnection();
		
		/**
		 * Get all quote IDs for the current order increment ID
		 */
		$quoteIds = $connection->fetchCol(
			$connection->select()
				->from(
					$this->resource->getTableName('quote'),
					['entity_id']
				)
				->where('reserved_order_id = ?', $order->getIncrementId())
		);
		
		if (empty($quoteIds) && $order->getQuoteId()) {
			$quoteIds = [$order->getQuoteId()];
		}
		
		/**
		 * Get Amasty Quote Increment ID
		 */
		$quoteIncrementId = $connection->fetchOne(
			$connection->select()
				->from(
					$this->resource->getTableName('amasty_quote'),
					['increment_id']
				)
				->where('quote_id IN (?)', $quoteIds)
				->order('quote_id DESC')
				->limit(1)
		);

        if ($quoteIncrementId) {
            $order->setData('amasty_quote_increment_id', $quoteIncrementId);
        }
        $order->setAdminUserAssistance($admin_username);
         // Save the order using the repository
         $this->orderRepository->save($order); 
    }

    public function createLog($msg){
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/AdminUsername.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
        
    }
    public function getAdminIdLoggedAsCustomer()
    {
        $adminId =  $this->getLoggedAsCustomerAdminIdInterface->execute();
        if(!empty($adminId) && ($adminId!=0)){
            $adminUser = $this->userFactory->create()->load($adminId);

            // Get the admin username
            $adminUsername = $adminUser->getUsername();

        return $adminUsername;
        }else{
            return '';
        }
    }
	/**
     * Create a log entry
     *
     * @param string $msg
     */
    public function createLogs($msg)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/afterPlace.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
}
