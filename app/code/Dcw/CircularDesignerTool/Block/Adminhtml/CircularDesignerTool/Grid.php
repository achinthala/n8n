<?php

namespace Dcw\CircularDesignerTool\Block\Adminhtml\CircularDesignerTool;

use Dcw\CircularDesignerTool\Model\Status;

/**
 * CircularDesignerTool grid.
 */

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    protected $_circulardesignertoolCollectionFactory;
    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Dcw\CircularDesignerTool\Model\ResourceModel\CircularDesignerTool\CollectionFactory
        $circulardesignertoolCollectionFactory,
        \Dcw\CircularDesignerTool\Model\ResourceModel\CircularDesignerToolTab\CollectionFactory
        $circulardesignertooltabCollectionFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        array $data = []
    ) {
         $this->_circulardesignertoolCollectionFactory = $circulardesignertoolCollectionFactory;
         $this->_circulardesignertooltabCollectionFactory = $circulardesignertooltabCollectionFactory;
         $this->jsonHelper = $jsonHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setId('circulardesignertoolGrid');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $storeViewId = $this->getRequest()->getParam('store');

        $collection = $this->_circulardesignertoolCollectionFactory->create()->setStoreViewId($storeViewId);
    
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'cdt_attribute_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );

        $this->addColumn(
            'cdt_tab_id',
            [
                'header' => __('Tab Name'),
                'sortable' => true,
                'index' => 'cdt_tab_id',
                'type'    =>'options',
                'options' => $this->getTabOptions(),
                'renderer' => \Dcw\CircularDesignerTool\Block\Adminhtml\Renderer\Tab::class
            ]
        );

        $this->addColumn(
            'cdt_attribute_title',
            [
                'header' => __('Attribute Title'),
                'index' => 'cdt_attribute_title',
                'class' => '',
                //'width' => '50px',
            ]
        );

        $this->addColumn(
            'cdt_attribute_value_display',
            [
                'header' => __('Attribute Value Display'),
                'index' => 'cdt_attribute_value_display',
                'class' => '',
                'width' => '50px',
            ]
        );
    
        $this->addColumn(
            'cdt_attribute_value',
            [
                'header' => __('Attribute Value'),
                'index' => 'cdt_attribute_value',
                'class' => '',
                'width' => '50px',
            ]
        );

        $this->addColumn(
            'description',
            [
                'header' => __('Description'),
                'index' => 'description',
                'class' => '',
            ]
        );

        $this->addColumn(
            'sections',
            [
                'header' => __('Sections'),
                'index' => 'sections',
                'class' => '',
            ]
        );

        $this->addColumn(
            'validation',
            [
                'header' => __('Validation'),
                'index' => 'validation',
                'class' => '',
            ]
        );

        $this->addColumn(
            'cdt_attribute_sort_order',
            [
                'header' => __('Attribute Sort Order'),
                'index' => 'cdt_attribute_sort_order',
                'class' => '',
                'width' => '50px',
            ]
        );
    
        $this->addColumn(
            'edit',
            [
                'header' => __('Edit'),
                'type' => 'action',
                'getter' => 'getId',
                'actions' => [
                    [
                        'caption' => __('Edit'),
                        'url' => ['base' => '*/*/edit'],
                        'field' => 'id',
                    ],
                ],
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'header_css_class' => 'col-action',
                'column_css_class' => 'col-action',
            ]
        );
        //$this->addExportType('*/*/exportCsv', __('CSV'));
        //$this->addExportType('*/*/exportXml', __('XML'));
        //$this->addExportType('*/*/exportExcel', __('Excel'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }
    
    public function getTabOptions()
    {
        $circulardesignertooltabCollection = $this->_circulardesignertooltabCollectionFactory->create();
        $circulardesignertooltabCollection->setOrder('cdt_tab_sort_order', 'ASC');
        $tabArr = [];
        foreach ($circulardesignertooltabCollection as $tab) {
            $tabArr[$tab->getCdtTabId()] = $tab->getCdtTabTitle();
        }
        return $tabArr;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl(
            '*/*/edit',
            ['id' => $row->getId()]
        );
    }

    public function jsonHelper()
    {
        return $this->jsonHelper;
    }
}
