<?php

namespace WeltPixel\GA4\Logger;

use Monolog\Logger;

class DebugCollectHandler extends \Magento\Framework\Logger\Handler\Base
{

    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/ga4-debug-collect.log';

}
