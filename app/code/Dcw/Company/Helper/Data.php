<?php
namespace Dcw\Company\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Customer\Api\GroupRepositoryInterface ;
use Magento\Eav\Model\Config;
use Magento\Customer\Model\CustomerFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Customer\Model\SessionFactory;

class Data extends AbstractHelper
{
    private $cart;
    private $productFactory;
    public function __construct(
        Context $context,
        Cart $cart,
        ProductRepositoryInterface $productRepository,
        GroupRepositoryInterface $groupRepository,
        Config $eavConfig,
        CustomerFactory $customerFactory,
        QuoteFactory $quoteFactory,
        Quote $quoteModel,
        SessionFactory $customerSeesionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
    ) {
        $this->cart = $cart;
        $this->productRepository = $productRepository;
        $this->groupRepository = $groupRepository;
        $this->eavConfig = $eavConfig;
        $this->customerFactory = $customerFactory;
        $this->quoteFactory = $quoteFactory;
        $this->quoteModel=$quoteModel;
        $this->customerSeesionFactory=$customerSeesionFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_productloader = $_productloader;
        $this->quoteRepository = $quoteRepository;

        parent::__construct($context);
    }

    public function updateCustomerData($customerId, $tshirtStatus)
    {
        $customer =  $this->customerFactory->create()->load($customerId);
        $customer->setData('declined_free_tshirt', 1);
        $customer->setData('tshirt_status', $tshirtStatus);
        $customer->setId($customerId);
        $customer->save();
    }

    public function addConfigurableProductTocart()
    {
        $addedStatus=0;
        //$parent = $this->productRepository->get("FREE-TSHIRT");
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('status', ['eq' => 1]);
        $collection->addAttributeToFilter('incstores_free_product', ['eq' => 1]);
        $collection->setPageSize(1);
        $free_tshirt_prod = [];
        foreach ($collection as $prod) {
            $free_tshirt_prod = $prod;
        }

        $quote = $this->quoteFactory->create();
        $customerSession=$this->customerSeesionFactory->create();
        $customerId=$customerSession->getCustomer()->getId();
        $customerQuote=$this->quoteModel->loadByCustomerId($quote, $customerId);
        $items=$quote->getAllItems();
        $prodSku=[];
        $isFreeTshirtExistInCart = 0;

        foreach ($items as $key => $item) {
            //$_product = $this->_productloader->create()->load($item->getProduct()->getId());
            if ($item->getProduct()->getIncstoresFreeProduct() == 1) {
                $isFreeTshirtExistInCart = 1;
                break;
            }
        }
    
        if ($isFreeTshirtExistInCart != 1 && !empty($free_tshirt_prod)) {
            $cart = $this->cart;
            $params['product'] = $free_tshirt_prod->getId();
            $params['qty'] = '1';
            $color = 'NA'; // enter your desired color option
            $size = 'NA'; // enter your desired size option
            $_children = $free_tshirt_prod->getTypeInstance()->getUsedProducts($free_tshirt_prod);
            $childSku="";
            foreach ($_children as $child) { 
            if ($child->getAttributeText('size') == $size) {
                 $childSku=$child->getSku();
            }
            }  
            $productAttributeOptions = $free_tshirt_prod->getTypeInstance(true)
            ->getConfigurableAttributesAsArray($free_tshirt_prod);
            $child = $this->productRepository->get($childSku);
            foreach ($productAttributeOptions as $option) { 
                   // if ($option ['label'] == 'Size') {
                        $options[$option['attribute_id']] = $child->getData($option['attribute_code']);
                  //  }
                
            }
            $params['super_attribute'] = $options; 
            $cart->addProduct($free_tshirt_prod, $params);
            $cart->save();
            //$cart->getQuote()->setTotalsCollectedFlag(false)->collectTotals();
            //$cart->getQuote()->save();
            /*$_quote = $this->quoteRepository->get($cart->getQuote()->getId());
            $_quote->setTotalsCollectedFlag(false);
            $_quote->setTriggerRecollect(1);
            $_quote->collectTotals();
            $_quote->getShippingAddress()->setCollectShippingRates(true);
            $_quote->save();
            $cart->save(); */

            $addedStatus=1;
        }
        return $addedStatus;
    }

    public function getGroupName($groupId)
    {
        $group = $this->groupRepository->getById($groupId);
        return $group->getCode();
    }

    public function getTshirtStatusValue($status)
    {
        $attribute = $this->eavConfig->getAttribute('customer', 'tshirt_status');
        $options = $attribute->getSource()->getAllOptions();
        $optionArray=[];
        foreach ($options as $key => $option) {
            if ($option["label"]!="") {
                $optionArray[$option["value"]]=$option["label"];
            }
        }
        
        return $optionArray[$status];
    }
    public function getTshirtDeclinedValue()
    {
        $attribute = $this->eavConfig->getAttribute('customer', 'tshirt_status');
        $options = $attribute->getSource()->getAllOptions();
        $value='';
        foreach ($options as $key => $option) {
            if ($option["label"]=="Declined") {
                $value=$option["value"];
            }
        }
        
        return $value;
    }
    public function getTshirtAvailedValue()
    {
        $attribute = $this->eavConfig->getAttribute('customer', 'tshirt_status');
        $options = $attribute->getSource()->getAllOptions();
        $value='';
        foreach ($options as $key => $option) {
            if ($option["label"]=="Availed") {
                $value=$option["value"];
            }
        }
        
        return $value;
    }
}
