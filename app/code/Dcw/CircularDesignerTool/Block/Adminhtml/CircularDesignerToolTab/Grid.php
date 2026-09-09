<?php

namespace Dcw\CircularDesignerTool\Block\Adminhtml\CircularDesignerToolTab;

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
        \Dcw\CircularDesignerTool\Model\ResourceModel\CircularDesignerToolTab\CollectionFactory
        $circulardesignertooltabCollectionFactory,
        array $data = []
    ) {
         $this->_circulardesignertooltabCollectionFactory = $circulardesignertooltabCollectionFactory;

        parent::__construct($context, $backendHelper, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setId('circulardesignertooltabGrid');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $storeViewId = $this->getRequest()->getParam('store');

        $collection = $this->_circulardesignertooltabCollectionFactory->create()->setStoreViewId($storeViewId);
    
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
                'index' => 'cdt_tab_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'filter' => false,
                'sortable' => false,
            ]
        );
        $this->addColumn(
            'cdt_tab_title',
            [
                'header' => __('Tab Title'),
                'index' => 'cdt_tab_title',
                'class' => 'xxx',
                'filter' => false,
                'sortable' => false,
                //'width' => '50px',
            ]
        );

        $this->addColumn(
            'cdt_tab_sort_order',
            [
                'header' => __('Tab Sort Order'),
                'index' => 'cdt_tab_sort_order',
                'filter' => false,
                'sortable' => false,
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

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }

    public function getRowUrl($row)
    {
        return $this->getUrl(
            '*/*/edit',
            ['id' => $row->getId()]
        );
    }
}
