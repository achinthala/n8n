<?php

namespace Dcw\MissedESD\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddCronConfig implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * AddCronConfig constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WriterInterface $configWriter
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $configWriter
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter = $configWriter;
    }

    /**
     * Apply patch
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->configWriter->save(
            'system/cron/default/schedule_generate_every',
            '1',
            'default',
            0
        );

        $this->configWriter->save(
            'system/cron/default/schedule_ahead_for',
            '1440',
            'default',
            0
        );

        $this->configWriter->save(
            'system/cron/default/schedule_lifetime',
            '15',
            'default',
            0
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}