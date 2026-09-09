<?php
/**
 *  @author Dcw Team
 */
namespace Dcw\Notifications\Block;

class Notification extends \Magento\Framework\View\Element\Template
{
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param array                                   $data
     */
    private $objectManager;
    protected $_registry;
    protected $_productCollectionFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Eav\Model\Config $eavConfig,
        array $data = []
    ) {
        $this->objectManager = $objectmanager;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_registry = $registry;
        $this->timezoneInterface = $timezoneInterface;
        $this->request = $request;
        $this->eavConfig = $eavConfig;
        parent::__construct($context, $data);
    }    

    public function getProductCollection()
    {
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
        $collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        $collection->addFieldToFilter ('incstores_pim_home_page_feature_flag', 
        array ('eq' => $this->eavConfig->getAttribute ('catalog_product', 'incstores_pim_home_page_feature_flag')->getSource ()->getOptionId ('Featured')),
        array ('eq' => $this->eavConfig->getAttribute ('catalog_product', 'incstores_pim_home_page_feature_flag')->getSource ()->getOptionId ('New')),
        array ('eq' => $this->eavConfig->getAttribute ('catalog_product', 'incstores_pim_home_page_feature_flag')->getSource ()->getOptionId ('Deal of the Month')),
        array ('eq' => $this->eavConfig->getAttribute ('catalog_product', 'incstores_pim_home_page_feature_flag')->getSource ()->getOptionId ('Free Shipping')),
        array ('eq' => $this->eavConfig->getAttribute ('catalog_product', 'incstores_pim_home_page_feature_flag')->getSource ()->getOptionId ('Quick Ship')),
        array ('eq' => $this->eavConfig->getAttribute ('catalog_product', 'incstores_pim_home_page_feature_flag')->getSource ()->getOptionId ('Best Seller')),
        );
        
        return $collection->count();
    }
    
    public function getAllData()
    {
        $resp_arr = [];
        $glob_notif_2 = $this->objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                            ->getValue('dcw_notifications_config/notification2/notification2_text');
        $resp_arr["glob_notif_2"] = $glob_notif_2;
        $request = $this->objectManager->get(\Magento\Framework\App\Action\Context::class)->getRequest();
        $resp_arr["full_action_name"] = $request->getFullActionName();

        if ($resp_arr['full_action_name'] == 'catalog_category_view') {
            $category = $this->_registry->registry('current_category'); //get current category
            $categoryId = $category->getId();
            $category2 = $this->objectManager->create(\Magento\Catalog\Model\Category::class)->load($categoryId);
            $cat_arr = $category2->getdata();

            $resp_arr["category_notification_bar2"] = isset($cat_arr['category_notification_bar2'])
                ? $cat_arr['category_notification_bar2'] : "";
                  
        }
    
        if ($resp_arr['full_action_name'] == 'catalog_product_view') {
            $product = $this->_registry->registry('current_product');//get current product
            $product2 = $this->objectManager->create(\Magento\Catalog\Model\Product::class)->load($product->getId());
            $resp_arr["incstores_pim_banner_msg_ext"] = $product2->getData('incstores_pim_banner_msg_ext');
        }

        return $resp_arr;
    }

    public function getAllData3()
    {
        $resp_arr = [];
        $glob_notif_3 = $this->objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                        ->getValue('dcw_notifications_config/notification3/notification3_text');
        $resp_arr["glob_notif_3"] = $glob_notif_3;
        $request = $this->objectManager->get(\Magento\Framework\App\Action\Context::class)->getRequest();
        $resp_arr["full_action_name"] = $request->getFullActionName();

        if ($resp_arr['full_action_name'] == 'catalog_category_view') {
            $category = $this->_registry->registry('current_category'); //get current category
            $categoryId = $category->getId();
            $category2 = $this->objectManager->create(\Magento\Catalog\Model\Category::class)->load($categoryId);
            $cat_arr = $category2->getdata();
         
            $resp_arr["category_notification_bar3"] = isset($cat_arr['category_notification_bar3'])
             ? $cat_arr['category_notification_bar3'] : "";

        }
    
        if ($resp_arr['full_action_name'] == 'catalog_product_view') {
            $product = $this->_registry->registry('current_product');//get current product
            $product2 = $this->objectManager->create(\Magento\Catalog\Model\Product::class)->load($product->getId());
            $resp_arr["incstores_pim_banner_msg_int"] = $product2->getData('incstores_pim_banner_msg_int');
        }

        return $resp_arr;
    }

    /**
     * Show Global notification on top page green section
     */
    public function getAllData4()
    {
        $resp_arr = [];
        $glob_notif_4 = $this->objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                        ->getValue('dcw_notifications_config/notification4/notification4_text');
        $global_notification_4_start_time = $this->objectManager
                        ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                        ->getValue('dcw_notifications_config/notification4/global_notification_4_start_time');
        $global_notification_4_end_time = $this->objectManager
                        ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                        ->getValue('dcw_notifications_config/notification4/global_notification_4_end_time');
        $resp_arr["glob_notif_4"] = $glob_notif_4;

        if ($global_notification_4_start_time != "" && $global_notification_4_end_time != "") {
            $start_date_time = strtotime($global_notification_4_start_time);
            $end_date_time = strtotime($global_notification_4_end_time);
            $current_datetime = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $cur_date_time = strtotime($current_datetime);

            if ($start_date_time <= $cur_date_time && $end_date_time >= $cur_date_time) {
                $resp_arr["active"] = 1;
            } else {
                $resp_arr["active"] = 0;
            }

        } else {
            $resp_arr["active"] = 1;
        }

        //$request = $this->objectManager->get(\Magento\Framework\App\Action\Context::class)->getRequest();
        $resp_arr["full_action_name"] = $this->getRequest()->getParam('full_action_name');

        if ($resp_arr['full_action_name'] == 'catalog_category_view') {
            $categoryId = $this->getRequest()->getParam('cat_prod_id');
            $category2 = $this->objectManager->create(\Magento\Catalog\Model\Category::class)->load($categoryId);
            $cat_arr = $category2->getdata();
                   
            $resp_arr["category_notification_bar4"] =
             isset($cat_arr['category_notification_bar4']) ? $cat_arr['category_notification_bar4'] : "";
        }
    
        if ($resp_arr['full_action_name'] == 'catalog_product_view') {
            $productId = $this->getRequest()->getParam('cat_prod_id');
            $product2 = $this->objectManager->create(\Magento\Catalog\Model\Product::class)->load($productId);
            $resp_arr["incstores_banner_marketing_external"] = $product2->getData('incstores_banner_marketing_external');
        }

        return $resp_arr;
    }
	
	
	 public function getSiteWideBannerText()
    {
        $resp_arr = [];
        $glob_notif_5 = $this->objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                        ->getValue('dcw_notifications_config/notification5/notification_sitewide');
        $resp_arr["glob_notif_5"] = $glob_notif_5;

        $resp_arr["active"] = 1;

        return $resp_arr;
    }

    /**
     * get current fullActionName
     */
    public function fullActionName()
    {
        return  $this->request->getFullActionName();
    }

    /**
     * get current category  id
     */
    public function getCurrentCatId()
    {
        $category = $this->_registry->registry('current_category'); //get current category
        return $category->getId();
    }

    /**
     * get current product id
     */
    public function getCurrentProdId()
    {
        $product = $this->_registry->registry('current_product'); //get current product
        return $product->getId();
    }
}
