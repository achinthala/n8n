<?php 
namespace Narvar\Accord\Logger\Handler;

use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;

class Custom extends Base
{
    protected $fileName = '/var/log/narvarcustom.log';
    protected $loggerType = Logger::DEBUG;
}
