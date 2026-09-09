<?php
namespace Dcw\RequestQuote\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Backend\Model\Auth\Session;
use Magento\User\Model\UserFactory;


class RequestQuoteAfterObserver implements ObserverInterface
{
    protected $getLoggedAsCustomerAdminIdInterface;
    protected $quoteRepository;
    protected $resource;
    protected $session;
    protected $userFactory;

    public function __construct(
        \Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminIdInterface,
        \Amasty\RequestQuote\Api\QuoteRepositoryInterface $quoteRepository,
        \Magento\Framework\App\ResourceConnection $resource,
        Session $session,
        UserFactory $userFactory
    ) {
        
        $this->getLoggedAsCustomerAdminIdInterface = $getLoggedAsCustomerAdminIdInterface;
        $this->quoteRepository = $quoteRepository;
        $this->resource = $resource;
        $this->session = $session;
        $this->userFactory = $userFactory;
        
    }

    public function execute(Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $admin_username = $this->getAdminIdLoggedAsCustomer();
        //$this->createLog('admin_username = '.$admin_username);
        //$this->createLog('quote_id = '.$quote->getId());
        if (!empty($admin_username)) {
           // $quote->setAdminUserAssistance($admin_username);
            //$this->quoteRepository->save($quote);

            $connection = $this->resource->getConnection();
            $quote_table = $connection->getTableName("quote");

            $where = ['entity_id = ?' => $quote->getId()];
            $connection->update($quote_table, ['admin_user_assistance' => $admin_username], $where);

            //$this->createLog('admin_username SAVED = '.$admin_username);
        }
        
    }

    public function createLog($msg){
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/request_quote_submit.log');
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
}
