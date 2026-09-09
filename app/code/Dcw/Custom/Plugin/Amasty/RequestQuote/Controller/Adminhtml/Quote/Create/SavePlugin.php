<?php
namespace Dcw\Custom\Plugin\Amasty\RequestQuote\Controller\Adminhtml\Quote\Create;

use Amasty\RequestQuote\Model\QuoteRepository;
use Magento\Framework\App\ResourceConnection;
use Amasty\RequestQuote\Model\Quote\Backend\FormDataProcessor;

class SavePlugin
{
    protected $quoteRepository;
	protected $resourceConnection;
	private $formDataProcessor;
    public function __construct(
        QuoteRepository $quoteRepository,
		ResourceConnection $resourceConnection,
		FormDataProcessor $formDataProcessor
    ) {
        $this->quoteRepository = $quoteRepository;
		$this->resourceConnection = $resourceConnection;
		$this->formDataProcessor = $formDataProcessor;
    }
	public function afterExecute(
        \Amasty\RequestQuote\Controller\Adminhtml\Quote\Create\Save $subject,
        $result
    ) {
		$connection = $this->resourceConnection->getConnection();
		$table = $connection->getTableName('quote');
		$model = $this->formDataProcessor->getQuoteEditModel();
		$quoteId = $model->getQuote()->getId();
		
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