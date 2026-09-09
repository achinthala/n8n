<?php
/**
 *  @author Dcw Team
 */
namespace Dcw\SplitPayment\Observer;
 
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
 
class SplitPaymentAfter implements ObserverInterface
{
    /**
     * @var \Dcw\SplitPayment\Block\Index
     */
    protected $splitPaymentBlock;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Framework\App\ResourceConnection->getConnection()
     */
    protected $connection;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
 
    public function __construct(
        \Dcw\SplitPayment\Block\Index $splitPaymentBlock,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Customer\Model\Session $customerSession,
        LoggerInterface $logger
    ) {
        $this->splitPaymentBlock = $splitPaymentBlock;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->customerSession = $customerSession;
        $this->logger = $logger;
    }

    /**
     * Update 'split_payment_stored_card' table 'payment_amount' column to 0
     * after place order success
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->customerSession->isLoggedIn()) {
            $split_payment_stored_card_table = $this->connection->getTableName("split_payment_stored_card");
            $storedCardsSplitPayment = $this->splitPaymentBlock->getStoredCardsSplitPayment();
            foreach ($storedCardsSplitPayment as $card) {
                $where = ['paradoxlabs_stored_card_id = ?' => $card->getId()];
                $this->connection->update(
                    $split_payment_stored_card_table,
                    ['payment_amount' => 0],
                    $where
                );
            }
        }
    }
}
