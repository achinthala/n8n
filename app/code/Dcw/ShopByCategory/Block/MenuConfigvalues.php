<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog Categories menu config
 */
namespace Dcw\ShopByCategory\Block;

use Magento\Framework\View\Element\Template;

class MenuConfigvalues extends Template
{
	/**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @param Template\Context $context
     * @param  \Magento\Framework\App\Config\ScopeConfigInterface $_scopeConfig
     * @param array $data
     */

    public function __construct(
        Template\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_scopeConfig = $scopeConfig;
    }

     /**
     * Return configaration fields
     *
     * @param string|int $field
     * @param string|int $storeId
     * @return string
     */
    public function getConfigValue($field, $storeId = null)
    {
        return $this->_scopeConfig->getValue(
            $field,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    } 

    /**
     * Return category Ids
     *
     * @return string
     */
    public function getRemoveLinkCategoryIds()
    {
        return $this->getConfigValue('dcw_menu_configaration/menulinkremovecattab/menulinkremovecat');
    }
}
