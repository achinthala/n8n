<?php
/**
 * Override Amasty FAQ Suggest Controller to support product ID filtering
 */
namespace Dcw\Faq\Controller\Search;

use Amasty\Faq\Controller\Search\Suggest as AmastySuggest;
use Magento\Framework\App\Action\Context;
use Amasty\Faq\Model\Search\Autocomplete\DataProvider as Autocomplete;
use Magento\Framework\Controller\ResultFactory;

class Suggest extends AmastySuggest
{
    /**
     * @var Autocomplete
     */
    private $autocomplete;

    /**
     * @param Context $context
     * @param Autocomplete $autocomplete
     */
    public function __construct(
        Context $context,
        Autocomplete $autocomplete
    ) {
        $this->autocomplete = $autocomplete;
        parent::__construct($context, $autocomplete);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        if (!$this->getRequest()->getParam('q', false)) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_url->getBaseUrl());

            return $resultRedirect;
        }

        // Get product ID from request if provided
        $productId = $this->getRequest()->getParam('product_id');
        
        // Pass product ID to autocomplete data provider
        $autocompleteData = $this->autocomplete->getItems($productId);
        $responseData = [];
        foreach ($autocompleteData as $resultItem) {
            $responseData[] = $resultItem->toArray();
        }
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseData);

        return $resultJson;
    }
}

