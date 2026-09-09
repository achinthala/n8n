<?php
namespace Dcw\SplitPayment\Block;

class Index extends \Magento\Framework\View\Element\Template
{
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Customer\Helper\Address $customerAddressHelper,
        \Magento\Directory\Model\Country $country,
        \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Helper\Image $imageHelperFactory,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Customer\Model\SessionFactory $cSession,
        \Magento\Customer\Block\Account\AuthorizationLink $authorizationLink,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Directory\Model\Currency $currency,
        array $data = []
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->timezoneInterface = $timezoneInterface;
        $this->cart = $cart;
        $this->customerAddressHelper = $customerAddressHelper;
        $this->country = $country;
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->customerSession = $customerSession;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->imageHelperFactory = $imageHelperFactory;
        $this->_addressConfig = $addressConfig;
        $this->cSession = $cSession;
        $this->authorizationLink = $authorizationLink;
        $this->_json = $json;
        $this->currency = $currency; 
        parent::__construct($context, $data);
    }
    /**
     * Preparing global layout
     *
     * @return $this
     */
    public function _prepareLayout()
    {
        //$this->_addBreadcrumbs();
        $this->pageConfig->getTitle()->set('Split Payment');
        $this->pageConfig->setKeywords('');
        $this->pageConfig->setDescription('');
            
        return parent::_prepareLayout();
    }
    
    /*protected function _addBreadcrumbs()
    {
        if (($breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs'))
        ) {
            $breadcrumbsBlock->addCrumb(
                'home',
                [
                'label' => __('Home'),
                'title' => __('Go to Home Page'),
                'link' => $this->storeManager->getStore()->getBaseUrl()
                ]
            );
            $breadcrumbsBlock->addCrumb(
                'circulardesignertool',
                [
                'label' => __('CircularDesignerTool'),
                ]
            );
        }
    }*/

    public function getConfigValue($store_config_field)
    {
        return $this->scopeConfig->getValue(
            $store_config_field,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getStoreId()
        );
    }

    public function getCurrentDateTime($format = '')
    {
        if (!empty($format)) {
            $currentDateTime = $this->timezoneInterface->date()->format($format);
        } else {
            $currentDateTime = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        }
        return $currentDateTime;
    }

    public function getRegionsOfCountry($countryCode) {
        $regionCollection = $this->country->loadByCode($countryCode)->getRegions();
        $regions = $regionCollection->loadData()->toOptionArray(false);
        return $regions;
    }

    public function getCurrentCustomer() {
        return $this->customerSession->getCustomer();
    }

    public function checkCurrentCustomerLogin() {
        return $this->customerSession->isLoggedIn();
    }

    public function getCurntCust() {
        $customer = $this->cSession->create();
        return $customer->getCustomer();
    }

    public function checkAuthorizationCustomerLogin() {
        return $this->authorizationLink->isLoggedIn();
    }

    public function getImageHelper() {
        return $this->imageHelperFactory;
    }

    public function getCurrentQuote()
    {
        return $this->cart->getQuote();
    }

    public function customerAddressHelper()
    {
        return $this->customerAddressHelper;
    }

    public function getStoredCardsSplitPayment() {
        $storedCards = $this->cardCollectionFactory->create();
        $storedCards->addFieldToFilter('method', 'authnetcim');
        $storedCards->addFieldToFilter('customer_id', $this->getCurrentCustomer()->getId());
        $storedCards->addFieldToFilter('active', 1);
        $storedCards->addFieldToFilter('saved_in_splitpayment', 1); // 1 -> saved CC card

        $split_payment_stored_card_table = $this->connection->getTableName("split_payment_stored_card");
        $storedCards->getSelect()->joinLeft(
            ['s_p_s_c' => $split_payment_stored_card_table],
            'main_table.id = s_p_s_c.paradoxlabs_stored_card_id',
            ['payment_amount']
        );
        return $storedCards;
    }

    /*public function getStoredGcCardsSplitPayment() {
        $storedCards = $this->cardCollectionFactory->create();
        $storedCards->addFieldToFilter('method', 'authnetcim');
        $storedCards->addFieldToFilter('customer_id', $this->getCurrentCustomer()->getId());
        $storedCards->addFieldToFilter('active', 1);
        $storedCards->addFieldToFilter('saved_in_splitpayment', 2); // 1 -> saved CC card | 2 -> saved GC card

        $split_payment_stored_card_table = $this->connection->getTableName("split_payment_stored_card");
        $storedCards->getSelect()->joinLeft(
            ['s_p_s_c' => $split_payment_stored_card_table],
            'main_table.id = s_p_s_c.paradoxlabs_stored_card_id',
            ['payment_amount']
        );
        $data = $storedCards->getFirstItem();
        return $data;
    }*/

    public function getFormatAddress($addess)
    {
        $renderer = $this->_addressConfig->getFormatByCode('html')->getRenderer();
        return str_replace('T: ','',$renderer->renderArray($addess));
    }

    public function jsonDecodeItemOption($item)
    {
        $optionValueArr=[];
        if ($this->isSampleCartItem($item)) {
            $additionalOptions = $item->getOptionByCode('additional_options');
            if (!empty($additionalOptions)) {
                $option_val = $this->_json->unserialize($additionalOptions->getValue());
                if (isset($option_val) && $option_val!="") {
                    foreach ($option_val as $option) {
                        if ($option['label']=="Configurable Product Id" || 
                        $option['label']== "Configurable Product Url" || 
                        $option['label']=="Configurable Product Image" || 
                        $option['label'] == "Product Name") {
                            continue;
                        }
                            $optionValueArr[$option['label']] = $option['value'];
                    }
                }
            }
        } elseif ($this->isCbcCartItem($item)) {
            $additionalOptions = $item->getOptionByCode('additional_options');
            if (!empty($additionalOptions)) {
                $option_val = $this->_json->unserialize($additionalOptions->getValue());
                if (isset($option_val) && $option_val!="") {
                    foreach ($option_val as $option) {
                        if ($option['label']=="Configurable Product Id" || 
                        $option['label']== "Configurable Product Url" || 
                        $option['label']=="Configurable Product Image" || 
                        $option['label'] == "Product Name") {
                            continue;
                        }
                            $optionValueArr[$option['label']] = $option['value'];
                    }
                }
            }
        } else {
            $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
            if (!empty($options['attributes_info'])) {
                foreach ($options['attributes_info'] as $option) {
                    $optionValueArr[$option['label']] = $option['value'];
                }
            }
        }
        return $optionValueArr;
        
    }

    public function isSampleCartItem($item)
    {
        $is_sampleCartItem = 0;
       
        $additionalOptions = $item->getOptionByCode('additional_options');
        
        if (!empty($additionalOptions)) {
            $option_val = $this->_json->unserialize($additionalOptions->getValue());
            if (!empty($option_val['sample_color'])) {
                $is_sampleCartItem = 1;
            }
        }
        
        return $is_sampleCartItem;
    }

    public function isCbcCartItem($item)
    {
        $is_cbcCartItem = 0;
       
        $additionalOptions = $item->getOptionByCode('additional_options');
        
        if (!empty($additionalOptions)) {
            $option_val = $this->_json->unserialize($additionalOptions->getValue());
            if (!empty($option_val['flooring_color'])) {
                $is_cbcCartItem = 1;
            }
        }
        
        return $is_cbcCartItem;
    }

    public function getCurrencySymbol()
    {
        return $this->currency->getCurrencySymbol();
    }

}
