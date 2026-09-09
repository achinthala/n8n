<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Dcw\Company\Controller\Sidebar;

use Exception;
use Magento\Checkout\Model\Sidebar;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory as ResultRedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Quote\Model\ResourceModel\Quote\Item;
use Magento\Customer\Model\Session;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory;
use Dcw\Company\Helper\Data;

/**
 * Controller for removing quote item from shopping cart.
 */
class RemoveItem extends \Magento\Checkout\Controller\Sidebar\RemoveItem
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResultJsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var ResultRedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var Sidebar
     */
    protected $sidebar;

    /**
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var CollectionFactory
     */
    protected $quoteItemCollectionFactory;
    /**
     * @var Session
     */
    protected $customerSession;
    /**
     * @var Data
     */
    protected $dataHelper;
    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param ResultJsonFactory $resultJsonFactory
     * @param ResultRedirectFactory $resultRedirectFactory
     * @param Sidebar $sidebar
     * @param Validator $formKeyValidator
     * @param LoggerInterface $logger
     * @param CollectionFactory $quoteItemCollectionFactory
     * @param Session $customerSession
     * @param Data $dataHelper
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        ResultJsonFactory $resultJsonFactory,
        ResultRedirectFactory $resultRedirectFactory,
        Sidebar $sidebar,
        Validator $formKeyValidator,
        LoggerInterface $logger,
        CollectionFactory $quoteItemCollectionFactory,
        Session $customerSession,
        Data $dataHelper,
        \Magento\Catalog\Model\ProductFactory $_productloader
    ) {
        parent::__construct(
            $context,
            $request,
            $resultJsonFactory,
            $resultRedirectFactory,
            $sidebar,
            $formKeyValidator,
            $logger
        );
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->sidebar = $sidebar;
        $this->formKeyValidator = $formKeyValidator;
        $this->logger = $logger;
        $this->quoteItemCollectionFactory = $quoteItemCollectionFactory;
        $this->customerSession = $customerSession;
        $this->dataHelper = $dataHelper;
        $this->_productloader = $_productloader;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        if (!$this->formKeyValidator->validate($this->request)) {
            return $this->resultRedirectFactory->create()
                ->setPath('*/cart/');
        }
        $error = '';
        $itemId = (int)$this->request->getParam('item_id');
        $quoteItemCollection = $this->quoteItemCollectionFactory->create();
        $quoteItem = $quoteItemCollection
            ->addFieldToSelect('*')
            ->addFieldToFilter('item_id', [$itemId])
            ->getFirstItem();
        $skuArray=[];
        // retrieve the quote item options
        $quoteItemOptions = $quoteItem->getOptions();
        foreach ($quoteItemOptions as $key => $item) {
            //$_product = $this->_productloader->create()->load($item->getProduct()->getId());
            $sku=$item->getProduct()->getSku();
            if ($item->getProduct()->getIncstoresFreeProduct() == 1) {
                if (!in_array($sku, $skuArray)) {
                    $skuArray[]=$sku;
                }
            }
        }

        try {
            if (count($skuArray)>0 && $this->customerSession->isLoggedIn()) {
                $customerData = $this->customerSession->getCustomer()->getData();
                $customerId=$customerData['entity_id'];
                $tshirtStatus= $this->dataHelper->getTshirtDeclinedValue();
                $this->dataHelper->updateCustomerData($customerId, $tshirtStatus);
                   
            }
            $this->sidebar->checkQuoteItem($itemId);
            $this->sidebar->removeQuoteItem($itemId);
        } catch (LocalizedException $e) {
            $error = $e->getMessage();
        } catch (\Zend_Db_Exception $e) {
            $this->logger->critical($e);
            $error = __('An unspecified error occurred. Please contact us for assistance.');
        } catch (Exception $e) {
            $this->logger->critical($e);
            $error = $e->getMessage();
        }

        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($this->sidebar->getResponseData($error));
        return $resultJson;
    }
}
