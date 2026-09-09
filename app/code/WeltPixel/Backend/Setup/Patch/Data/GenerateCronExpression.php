<?php
namespace WeltPixel\Backend\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class GenerateCronExpression implements DataPatchInterface, PatchVersionInterface
{
    const UPDATE_CRON_STRING_PATH = "weltpixel/crontab/license";

    /** @var WriterInterface */
    private $configWriter;

    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WriterInterface $configWriter
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $configWriter)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter = $configWriter;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $cronExpression = $this->_generateCronExpression();
        $this->configWriter->save(self::UPDATE_CRON_STRING_PATH, $cronExpression);

        $this->moduleDataSetup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.1.1';
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return string
     */
    protected function _generateCronExpression()
    {
        $minute = rand(0, 59);
        $hour = rand(-2, 6);
        $dayOfWeek = rand(0, 6);
        if ($hour < 0) {
            $hour += 24;
        }
        return $minute . ' ' . $hour . ' * * ' . $dayOfWeek;
    }
}
