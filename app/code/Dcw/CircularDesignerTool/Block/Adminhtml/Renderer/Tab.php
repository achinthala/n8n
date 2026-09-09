<?php
namespace Dcw\CircularDesignerTool\Block\Adminhtml\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use \Dcw\CircularDesignerTool\Model\ResourceModel\CircularDesignerToolTab\CollectionFactory;

class Tab extends AbstractRenderer
{
  
    public function __construct(
        \Magento\Backend\Block\Context $context,
        StoreManagerInterface $storemanager,
        CollectionFactory $circulardesignertoolCollectionFactory,
        array $data = []
    ) {
          $this->_circulardesignertoolCollectionFactory = $circulardesignertoolCollectionFactory;
        $this->_storeManager = $storemanager;
        parent::__construct($context, $data);
    }
    public function render(DataObject $row)
    {
       
        $value = parent::render($row);
        $circulardesignertoolCollection = $this->_circulardesignertoolCollectionFactory->create();
        $circulardesignertoolCollection->addFieldTOFilter('cdt_tab_id', $value);
        $collection = $circulardesignertoolCollection->getData();
        if (!empty($collection)) {
            return $collection[0]['cdt_tab_title'];
        }
    }
}
