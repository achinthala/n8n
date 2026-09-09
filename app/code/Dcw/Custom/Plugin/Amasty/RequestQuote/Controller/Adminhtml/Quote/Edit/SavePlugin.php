<?php
namespace Dcw\Custom\Plugin\Amasty\RequestQuote\Controller\Adminhtml\Quote\Edit;

use Amasty\RequestQuote\Model\QuoteRepository;
use Magento\Framework\App\ResourceConnection;

class SavePlugin
{
    protected $quoteRepository;
	protected $resourceConnection;
    public function __construct(
        QuoteRepository $quoteRepository,
		ResourceConnection $resourceConnection
    ) {
        $this->quoteRepository = $quoteRepository;
		$this->resourceConnection = $resourceConnection;
    }
	public function afterExecute(
        \Amasty\RequestQuote\Controller\Adminhtml\Quote\Edit\Save $subject,
        $result
    ) {
		$connection = $this->resourceConnection->getConnection();
		$table = $connection->getTableName('quote');
		$quoteId = $subject->getRequest()->getParam('quote_id');
		$cartLabel = $subject->getRequest()->getParam('cart_label');

		if ($cartLabel!="") {
			$connection->insertOnDuplicate($table, [
				'entity_id' => $quoteId,
				'cart_label' => $cartLabel
			], ['cart_label']);
		}
        
        return $result;
    }
}