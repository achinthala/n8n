<?php

namespace Narvar\Accord\Helper;

use Exception;

class AccordException extends Exception
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        $message = 'Accord Exception : ' . $message;
        parent::__construct($message, $code, $previous);
    }
}
