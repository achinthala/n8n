<?php
/**
 * Override Amasty FAQ Autocomplete DataProvider to filter by product ID
 */
namespace Dcw\Faq\Model\Search\Autocomplete;

use Amasty\Faq\Model\Search\Autocomplete\DataProvider as AmastyDataProvider;
use Amasty\Faq\Model\ConfigProvider;
use Amasty\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Amasty\Faq\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Amasty\Faq\Model\Url;
use Magento\Framework\App\Http\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\Autocomplete\ItemFactory;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Context as CustomerContext;

class DataProvider extends AmastyDataProvider
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Context
     */
    protected $httpContext;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var Url
     */
    protected $url;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var QuestionCollectionFactory
     */
    protected $questionCollectionFactory;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @param ItemFactory $itemFactory
     * @param QuestionCollectionFactory $questionCollectionFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param RequestInterface $request
     * @param Context $httpContext
     * @param StoreManagerInterface $storeManager
     * @param ConfigProvider $configProvider
     * @param Url $url
     */
    public function __construct(
        ItemFactory $itemFactory,
        QuestionCollectionFactory $questionCollectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        RequestInterface $request,
        Context $httpContext,
        StoreManagerInterface $storeManager,
        ConfigProvider $configProvider,
        Url $url
    ) {
        $this->itemFactory = $itemFactory;
        $this->questionCollectionFactory = $questionCollectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->request = $request;
        $this->httpContext = $httpContext;
        $this->storeManager = $storeManager;
        $this->configProvider = $configProvider;
        $this->url = $url;
        parent::__construct(
            $itemFactory,
            $questionCollectionFactory,
            $categoryCollectionFactory,
            $request,
            $httpContext,
            $storeManager,
            $configProvider,
            $url
        );
    }

    /**
     * @return array
     */
    public function getItems($productId = null)
    {
        // Get product ID from request if not provided as parameter
        if ($productId === null) {
            $productId = $this->request->getParam('product_id');
        }
        
        $result = [];
        $query = $this->request->getParam(QueryFactory::QUERY_VAR_NAME);
        $storeId = $this->storeManager->getStore()->getId();
        $customerAuth = (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
        $customerGroup = $this->httpContext->getValue(CustomerContext::CONTEXT_GROUP);
        $urlKey = $this->configProvider->getUrlKey();

        if ($this->configProvider->isShowCategoryInSearch() && !$productId) {
            foreach ($this->getCategoryCollection($query, $storeId, $customerGroup)->getData() as $item) {
                $result[] = $this->itemFactory->create([
                    'title' => $item['title'],
                    'url' => $this->url->getEntityUrl([$urlKey, $item['url_key']])
                ]);
            }
        }
        
        // Get question collection with optional product filter
        $questionCollection = $this->getQuestionCollection($query, $customerAuth, $storeId, $customerGroup, $productId);
        
        foreach ($questionCollection->getData() as $item) {
            $result[] = $this->itemFactory->create([
                'title' => $item['title'],
                'category' => $item['category'],
                'url' => $this->url->getEntityUrl([$urlKey, $item['url_key']])
            ]);
        }

        return $result;
    }

    /**
     * Get category collection
     *
     * @param string $query
     * @param int $storeId
     * @param int|null $customerGroup
     * @return \Amasty\Faq\Model\ResourceModel\Category\Collection
     */
    private function getCategoryCollection($query, $storeId, $customerGroup)
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->getAutosuggestCollection($query);
        $collection->addFrontendFilters(
            $storeId,
            null,
            $customerGroup
        );

        return $collection;
    }

    /**
     * Get question collection with optional product filter
     *
     * @param string $query
     * @param bool $customerAuth
     * @param int $storeId
     * @param int|null $customerGroup
     * @param int|null $productId
     * @return \Amasty\Faq\Model\ResourceModel\Question\Collection
     */
    private function getQuestionCollection($query, $customerAuth, $storeId, $customerGroup, $productId = null)
    {
        $collection = $this->questionCollectionFactory->create();
        $collection->getAutosuggestCollection($query);
        $collection->addFrontendFilters(
            $customerAuth,
            $storeId,
            null,
            $customerGroup
        );
        
        // Add product filter if product ID is provided
        if ($productId) {
            $collection->addProductFilter($productId);
        }

        return $collection;
    }
}

