<?php
declare(strict_types=1);

namespace Dcw\AiContentCreator\Logger;

use Magento\Framework\Logger\Monolog as FrameworkMonolog;
use Monolog\Handler\HandlerInterface;

/**
 * Dedicated logger for AI Content Creator API activity.
 */
class Logger extends FrameworkMonolog
{
    public function __construct(HandlerInterface $handler)
    {
        parent::__construct('dcw_ai_content', [$handler]);
    }
}
