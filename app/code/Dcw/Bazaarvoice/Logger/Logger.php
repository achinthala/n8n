<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);


namespace Dcw\Bazaarvoice\Logger;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Exception;
use Magento\Framework\App\State;
use Monolog\DateTimeImmutable;

/**
 * Class Logger
 *
 * @package Bazaarvoice\Connector\Logger
 */
class Logger extends \Bazaarvoice\Connector\Logger\Logger
{
  
    /**
     * @param string|array $message
     * @param array        $context
     *
     * @return bool 
     */
    public function debug($message, array $context = []): void
    {

        
        /* Create a new product object */
        $configProvider = \Magento\Framework\App\ObjectManager::getInstance()->create(\Bazaarvoice\Connector\Api\ConfigProviderInterface::class);
        if ($configProvider->isDebugEnabled()) {
            if(is_string($message)){
                $this->addRecord(static::DEBUG, $message, $context);
            }            
        }
    }
}