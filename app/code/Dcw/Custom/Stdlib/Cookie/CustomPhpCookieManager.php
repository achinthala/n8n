<?php

namespace Dcw\Custom\Stdlib\Cookie;

use Magento\Framework\Stdlib\Cookie\PhpCookieManager as CorePhpCookieManager;

class CustomPhpCookieManager extends CorePhpCookieManager
{
    // Increase the constants here
    const MAX_NUM_COOKIES = 200; // Custom max number of cookies 50
    const MAX_COOKIE_SIZE = 8192; // Custom max cookie size in bytes 4096
}
