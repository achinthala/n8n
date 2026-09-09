<?php
namespace Dcw\Faq\Block;

use Dcw\Faq\Model\Status;
/**
 *  Faq Block
 */

class Index extends \Amasty\Faq\Block\AbstractBlock
{
	protected $_coreRegistry;
	
	protected $_faqCollectionFactory;
	
	public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
		\Amasty\Faq\Model\ResourceModel\Question\CollectionFactory $collectionFactory,
		\Magento\Framework\Serialize\Serializer\Json $json,
		\Magento\Framework\App\ResourceConnection $resource,
		\Magento\Framework\Registry $registry,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
 		\Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
	) {
	    $this->_coreRegistry = $coreRegistry;
		$this->collectionFactory = $collectionFactory;
		$this->_json = $json;
		$this->resource = $resource;
		$this->connection = $resource->getConnection();
		$this->_registry = $registry;
		$this->scopeConfig = $scopeConfig;
 		$this->storeManager = $storeManager;
	    parent::__construct($context, $data);
	}
	/**
	 * Preparing global layout
	 *
	 * @return $this
	 */
	public function _prepareLayout()
	{  
	    $this->_addBreadcrumbs();
	    $this->pageConfig->getTitle()->set('Faq');
	    $this->pageConfig->setKeywords('');
	    $this->pageConfig->setDescription('');
		    
	    return parent::_prepareLayout();
	}
	
	 protected function _addBreadcrumbs()
	{
	    if (($breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs'))
	    ) {
		$breadcrumbsBlock->addCrumb(
		    'home',
		    [
			'label' => __('Home'),
			'title' => __('Go to Home Page'),
			'link' => $this->_storeManager->getStore()->getBaseUrl()
		    ]
		);
		$breadcrumbsBlock->addCrumb(
		    'faq',
		    [
			'label' => __('Faq'),
		    ]
		);
	    }
	}
	
	public function getClpFaqList()
	{
		$amasty_faq_question_product_category_table = 
		$this->connection->getTableName("amasty_faq_question_product_category");
		
		$faqCollection = $this->collectionFactory->create();
		$faqCollection->addFieldToFilter('status',1);
		$faqCollection->addFieldToFilter('visibility',1);
		$faqCollection->setOrder('position', 'ASC');

		if ($this->_registry->registry('current_category')) {
            $current_category = $this->_registry->registry('current_category');
            $current_category_id = $current_category->getId();

			$faqCollection->getSelect()->joinLeft(
				['question_product_category' => $amasty_faq_question_product_category_table],
				'main_table.question_id = question_product_category.question_id',
				[]
			)->where('question_product_category.category_id = ?', $current_category_id);
        }

		$collection = $faqCollection;
		return $collection; 
		
	}


	public function getLimitFullAnswer($question)
    {
		$fullTextLimit = $this->getConfigValue('amastyfaq/faq_page/limit_short_answer');
        if (!$this->getConfigValue('amastyfaq/faq_page/short_answer_behavior')
            && $shortAnswer = $question->getShortAnswer()
        ) {
            return $shortAnswer;
        }
        $answer = strip_tags(
            $this->getCleanedFullAnswer($question),
            ['b', 'a', 'i', 'u', 'strong', 'em', 'mark', 's', 'br']
        );

        if (mb_strlen($answer) < $fullTextLimit) {
            return $answer;
        }

        if (preg_match('/^(.{' . ((int)$fullTextLimit) . '}.*?)\b/isu', $answer, $shortAnswer)) {
            return $shortAnswer[1] . '...';
        }

        return '';
    }

    public function getCleanedFullAnswer($question): string
    {
        $parts = [
            'img' => '<img[^>]+\>',
            'widget' => '{{widget[^}]+}}',
            'html-body' => '#html-body[^}]+}',
            'style' => '<style>[^<]+<\/style>'
        ];
        $answer = $question->getAnswer();

        return $answer
            ? (string)preg_replace('/' . implode('|', $parts) . '/i', '', $answer)
            : '';
    }

	public function getConfigValue($path) {
		return $this->scopeConfig->getValue($path,
		\Magento\Store\Model\ScopeInterface::SCOPE_STORE,
		$this->storeManager->getStore()->getStoreId());
	}
    
}