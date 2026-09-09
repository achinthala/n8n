<?php
declare(strict_types=1);

namespace Dcw\AiContentCreator\Logger\Handler;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base as BaseHandler;
use Monolog\Logger as MonologLogger;

/**
 * Writes AI Content Creator logs to var/log/dcw_ai_content.log.
 */
class AiContent extends BaseHandler
{
    /**
     * @var int
     */
    protected $loggerType = MonologLogger::INFO;

    /**
     * @var string
     */
    protected $fileName = '/var/log/dcw_ai_content.log';

    public function __construct(
        DriverInterface $filesystem,
        $filePath = null,
        $fileName = null
    ) {
        parent::__construct($filesystem, $filePath, $fileName ?? $this->fileName);
    }
}
