<?php

declare(strict_types=1);

namespace Dcw\Notifications\Cron;

class AvailabilityNotificationsSet
{
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface  $configWriter
    ) {
        
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->timezoneInterface = $timezoneInterface;
        $this->storeManager = $storeManager;
        $this->_inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_scopeConfig = $scopeConfig;
        $this->_configWriter = $configWriter;
    }
    
    public function execute()
    {
        try {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/AvailabilityNotificationsCron.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info(__METHOD__);
            
            //$current_datetime = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            //$current_date = $this->timezoneInterface->date()->format('Y-m-d');
            $currentHour = $this->timezoneInterface->date()->format('G');
            $currentDayNumeric = $this->timezoneInterface->date()->format('w');
            
            $businessHourStart = $this->_scopeConfig->getValue(
                'dcw_notifications_config/notification/business_hour_start',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        
            $businessHourEnd = $this->_scopeConfig->getValue(
                'dcw_notifications_config/notification/business_hour_end',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        
            $daysOff = $this->_scopeConfig->getValue(
                'dcw_notifications_config/notification/days_off',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        
            if (!empty($daysOff)) {
                $daysOffArray = explode(",", $daysOff);
            } else {
                $daysOffArray = [];
            }
            
            $logger->info('current_hour='.$currentHour.
              ' | current_day_numeric='.$currentDayNumeric.
              ' | business_hour_start='.$businessHourStart.
              ' | business_hour_end='.$businessHourEnd.
              ' | days_off_arr='.json_encode($daysOffArray));
            
            $path ='dcw_notifications_config/notification/availability';
            if (($currentHour >= $businessHourStart &&
            $currentHour <= $businessHourEnd) &&
            !in_array($currentDayNumeric, $daysOffArray)) {
                $logger->info('Value set YES');
                $this->_configWriter->save($path, 1, $scope = $this->_scopeConfig::SCOPE_TYPE_DEFAULT, $scopeId = 0);
            } else {
                $logger->info('Value set No');
                $this->_configWriter->save($path, 0, $scope = $this->_scopeConfig::SCOPE_TYPE_DEFAULT, $scopeId = 0);
            }
            
        } catch (Exception $e) {
            $logger->info($e->getMessage());
        }
    
        return $this;
    }
}
