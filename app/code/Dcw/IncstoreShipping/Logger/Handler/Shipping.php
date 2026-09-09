<?php
declare(strict_types=1);

namespace Dcw\IncstoreShipping\Logger\Handler;

use Magento\Framework\Logger\Handler\Base as BaseHandler;
use Magento\Framework\Filesystem\DriverInterface;

class Shipping extends BaseHandler
{
    protected $bubble = false;

    protected $fileName = '/var/log/shippingAPI.log';

    public function __construct(
        DriverInterface $filesystem,
        $filePath = null,
        $fileName = null
    ) {
        parent::__construct($filesystem, $filePath, $fileName ?? $this->fileName);
    }
}

