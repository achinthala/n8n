<?php

namespace Narvar\Accord\Helper;

use Magento\Store\Model\ScopeInterface;
use Narvar\Accord\Helper\Constants\Constants;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ScopeConfigHelper
{
    private $constants;
    /**
     * Constructor
     *
     * @param Constants  $constants   Narvar\Accord\Helper\Constants\Constants
     **/
    public function __construct(
        Constants $constants
    ) {
        $this->constants    = $constants->getConstants();
    }

    public function getScope($configScope)
    {
        switch ($configScope) {
            case $this->constants['STORE_SCOPE']:
                return ScopeInterface::SCOPE_STORE;
            case $this->constants['WEBSITE_SCOPE']:
                return ScopeInterface::SCOPE_WEBSITE;
            default:
                return ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        }
    }
}
