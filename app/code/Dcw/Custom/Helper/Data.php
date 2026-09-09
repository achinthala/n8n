<?php

namespace Dcw\Custom\Helper;

/**
 * Helper Data
 */

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
   
    protected $quoteRepository;
	protected $resourceConnection;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
		\Amasty\RequestQuote\Model\QuoteRepository $quoteRepository,
		\Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->quoteRepository = $quoteRepository;
		$this->resourceConnection = $resourceConnection;
    }

    public function getCartLabel($quoteId)
    {
		$connection = $this->resourceConnection->getConnection();
		$tableName = $connection->getTableName('quote');
		$sql = "Select * FROM " . $tableName." WHERE entity_id=".$quoteId;
        $result = $connection->fetchRow($sql);
		
        return $result;
    }

    public function getCartLabelOptions()
    {
		return [
            ['value' => 'FCC', 'label' => __('FCC')],
            ['value' => 'Non FCC', 'label' => __('Non FCC')],
            ['value' => 'Hot QQ', 'label' => __('Hot QQ')],
            ['value' => 'Warm QQ', 'label' => __('Warm QQ')],
            ['value' => 'Engaged', 'label' => __('Engaged')],
            ['value' => 'Cold', 'label' => __('Cold')],
            ['value' => 'Do Not Email', 'label' => __('Do Not Email')],
            ['value' => 'Lost', 'label' => __('Lost')],
        ];
    }
}
