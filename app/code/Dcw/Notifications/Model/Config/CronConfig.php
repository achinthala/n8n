<?php

namespace Dcw\Notifications\Model\Config;

class CronConfig extends \Magento\Framework\App\Config\Value
{
    protected const CRON_STRING_PATH = 'crontab/default/jobs/dcw_availability_notifications_set/schedule/cron_expr';

    protected const CRON_MODEL_PATH = 'crontab/default/jobs/dcw_availability_notifications_set/run/model';

    protected $_configValueFactory;

    protected $_runModelPath = '';

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        $runModelPath = '',
        array $data = []
    ) {
        $this->_runModelPath = $runModelPath;
        $this->_configValueFactory = $configValueFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->_scopeConfig = $scopeConfig;
        $this->_logger = $logger;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }
    
    public function afterSave()
    {
        return parent::afterSave();

        //this we are not using in crontab.xml file so we added early return to avoid errors
        $business_hour_start = $this->_scopeConfig->getValue(
            'dcw_notifications_config/notification/business_hour_start',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $business_hour_end = $this->_scopeConfig->getValue(
            'dcw_notifications_config/notification/business_hour_end',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        
        $current_datetime = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        
        $cron_hourArr = [];
        $cron_hour = "0";

        if (!empty($business_hour_start)) {
            $cron_hourArr[] = $business_hour_start;
        }

        if (!empty($business_hour_end)) {
            $cron_hourArr[] = $business_hour_end;
        }
        
        if (!empty($cron_hourArr)) {
            $cron_hour = implode(',', $cron_hourArr);
        }

        $cronExprArray = [
            '0',
            $cron_hour,
            '*',
            '*',
            '*',
        ];

        $cronExprString = join(' ', $cronExprArray);
        
        try {
            $this->_configValueFactory->create()->load(
                self::CRON_STRING_PATH,
                'path'
            )->setValue(
                $cronExprString
            )->setPath(
                self::CRON_STRING_PATH
            )->save();
            $this->_configValueFactory->create()->load(
                self::CRON_MODEL_PATH,
                'path'
            )->setValue(
                $this->_runModelPath
            )->setPath(
                self::CRON_MODEL_PATH
            )->save();
        } catch (\Exception $e) {
            $this->_logger->debug($e->getMessage());
        }

        return parent::afterSave();
    }
}
