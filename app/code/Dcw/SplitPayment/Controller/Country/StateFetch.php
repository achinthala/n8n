<?php
namespace Dcw\SplitPayment\Controller\Country;
 
class StateFetch extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    
    protected $regionColFactory;
 
        public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Directory\Model\RegionFactory $regionColFactory)
    {    	
        $this->regionColFactory     	= $regionColFactory;
        $this->resultJsonFactory     	= $resultJsonFactory;
        parent::__construct($context);
    }
 
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();

        $result          	= $this->resultJsonFactory->create();
        $regions=$this->regionColFactory->create()->getCollection()->addFieldToFilter('country_id',$this->getRequest()->getParam('country_id'));
        $html = '';
        if(count($regions) > 0)
        {
                    $html.='<option selected="selected" value="">Please select a region, state or province.</option>';
                    foreach($regions as $state)
                    {
                        $html.= '<option  value="'.$state->getId().'">'.$state->getName().'</option>';
                    }
        }
        return $result->setData(['success' => true,'value'=>$html]);
   }
}