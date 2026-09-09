<?php
namespace Dcw\CircularDesignerTool\Block\Adminhtml\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;

class Image extends AbstractRenderer
{
    public function __construct(
        \Magento\Backend\Block\Context $context,
        StoreManagerInterface $storemanager,
        array $data = []
    ) {
        $this->_storeManager = $storemanager;
        parent::__construct($context, $data);
    }
    public function render(DataObject $row)
    {
        $storePath = $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        );
            $value = parent::render($row);
            $html = '<img width="60" height="60" src="'.$storePath.'circulardesignertool/'.$value.'" />';
            return $html;
    }
}
