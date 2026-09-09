<?php
declare(strict_types=1);

namespace Dcw\IncstoreShipping\Logger;

use Magento\Framework\Logger\Monolog as FrameworkMonolog;
use Monolog\Handler\HandlerInterface;

class Logger extends FrameworkMonolog
{
    public function __construct(HandlerInterface $handler)
    {
        parent::__construct('incstore_shipping', [$handler]);
    }
}
