<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dcw\CompareRestriction\Controller\Compare;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\ViewModel\Product\Checker\AddToCompareAvailability;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;

/**
 * Add item to compare list action.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Add extends \Magento\Catalog\Controller\Product\Compare implements HttpPostActionInterface
{
    /**
     * @var AddToCompareAvailability
     */
    private $compareAvailability;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Catalog\Model\Product\Compare\ItemFactory $compareItemFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory $itemCollectionFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\Visitor $customerVisitor
     * @param \Magento\Catalog\Model\Product\Compare\ListCompare $catalogProductCompareList
     * @param \Magento\Catalog\Model\Session $catalogSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param PageFactory $resultPageFactory
     * @param ProductRepositoryInterface $productRepository
     * @param AddToCompareAvailability|null $compareAvailability
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\Product\Compare\ItemFactory $compareItemFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory $itemCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Visitor $customerVisitor,
        \Magento\Catalog\Model\Product\Compare\ListCompare $catalogProductCompareList,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        PageFactory $resultPageFactory,
        ProductRepositoryInterface $productRepository,
        AddToCompareAvailability $compareAvailability = null
    ) {
        parent::__construct(
            $context,
            $compareItemFactory,
            $itemCollectionFactory,
            $customerSession,
            $customerVisitor,
            $catalogProductCompareList,
            $catalogSession,
            $storeManager,
            $formKeyValidator,
            $resultPageFactory,
            $productRepository
        );

        $this->compareAvailability = $compareAvailability
            ?: $this->_objectManager->get(AddToCompareAvailability::class);
    }

    /**
     * Add item to compare list.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setRefererUrl();
        }

        $items = $this->_itemCollectionFactory->create();
        if (!empty($items)) {
            if ($this->_customerSession->isLoggedIn()) {
                $items->setCustomerId($this->_customerSession->getCustomerId());
            } elseif ($this->_customerId) {
                $items->setCustomerId($this->_customerId);
            } else {
                $items->setVisitorId($this->_customerVisitor->getId());
            }
            $items->clear();
        }

        $compare_list=$this->getRequest()->getParam('compare-list');
        $idArray=[];
        if($compare_list!="") {
            /*if( strpos($compare_list, ',') !== false ) {
                $idArray=explode(",",$compare_list);

            } else {
                $idArray[]=$compare_list;
            }*/
            $idArray=explode(",",$compare_list);
        }
      
        foreach ($idArray as $key => $productId) {
            if ($productId && ($this->_customerVisitor->getId() || $this->_customerSession->isLoggedIn())) {
                $storeId = $this->_storeManager->getStore()->getId();
                try {
                    /** @var \Magento\Catalog\Model\Product $product */
                    $product = $this->productRepository->getById($productId, false, $storeId);
                } catch (NoSuchEntityException $e) {
                    $product = null;
                }
    
                if ($product && $this->compareAvailability->isAvailableForCompare($product)) {
                    $this->_catalogProductCompareList->addProduct($product);
                    $this->_eventManager->dispatch('catalog_product_compare_add_product', ['product' => $product]);
                }
    
                $this->_objectManager->get(\Magento\Catalog\Helper\Product\Compare::class)->calculate();
            }
        }

        $catId = $this->getRequest()->getParam('catId');
        $argument = ['catid' => $catId];       
        $resultRedirect->setPath('catalog/product_compare/index/', $argument);
        return $resultRedirect;
    }
}
