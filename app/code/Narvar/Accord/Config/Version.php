<?php

namespace Narvar\Accord\Config;

/**
 * Config backend model for version display.
 */
class Version extends \Magento\Framework\App\Config\Value
{
    protected $moduleResource;

    /**
     * @param \Magento\Framework\Module\ResourceInterface $moduleResource
     */
    public function __construct(
        \Magento\Framework\Module\ResourceInterface $moduleResource
    ) {
        $this->moduleResource = $moduleResource;
    }

    /**
     * Inject current installed module version as the config value.
     *
     * @return void
     */
    public function afterLoad()
    {
        $version = $this->moduleResource->getDbVersion('Narvar_Accord');

        $this->setValue($version);
    }
}
