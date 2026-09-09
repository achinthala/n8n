<?php
declare(strict_types=1);

namespace Dcw\ShopByCategory\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ArgumentInterface
{
    public const XML_PATH_DCW_MENU_CONFIGARATION_MENULINKREMOVECATTAB_ALL_CATEGORY_ID =
        'dcw_menu_configaration/menulinkremovecattab/all_category_id';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function getMenuConfigurationAllCategoryId(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_DCW_MENU_CONFIGARATION_MENULINKREMOVECATTAB_ALL_CATEGORY_ID,
            ScopeInterface::SCOPE_STORE
        );
    }
}
