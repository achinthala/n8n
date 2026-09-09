<?php

namespace Narvar\Accord\Helper\Constants;

class Constants
{
    public const MODULE_NAME      = 'Narvar_Accord';
    public const STORE_SCOPE      = 'STORE';
    public const WEBSITE_SCOPE    = 'WEBSITE';
    public const GLOBAL_SCOPE     = 'GLOBAL';
    public const AUTH_KEY         = 'narvar_auth';
    public const RETAILER_MONIKER = 'narvar_retailer_moniker';
    public const DEBUG_MODE       = 'narvar_debug_mode';
    public const PRODUCTION_ENVIRONMENT       = 'narvar_production_environment';
    public const INTEGRATION_TOKEN            = 'narvar_integration_token';
    public const INTEGRATION_TOKEN_SECRET     = 'narvar_integration_token_secret';
    public const STORE_BASE_URL               = 'narvar_store_base_url';
    public const ACCORD_ENABLED               = 'narvar_accord_enabled';
    public const RETAILER_MONIKER_HEADER      = 'x-magento-retailer';
    public const EVENT_HEADER                 = 'x-magento-event';
    public const STORE_HEADER                 = 'x-magento-store';
    public const HMAC_HEADER                  = 'x-magento-hmac-sha256';
    public const INVALIDARGUMENTEXCEPTION     = 'InvalidArgumentException';
    public const INVALIDCREDENTIALSEXCEPTION  = 'Invalid Credentials';
    public const AUTH_HOST                    = 'narvar_auth_api_host';
    public const AUTH_API                     = 'narvar_auth_api';
    public const DATA_HOST                    = 'narvar_data_api_host';
    public const NO_FLAKE_HOST                = 'narvar_no_flake_host';
    public const NO_FLAKE_API                 = 'no_flake_api';
    public const NO_FLAKE_TAG                 = 'noflake.magento';
    public const NO_FLAKE_SOURCE              = 'magento_extension';
    public const NO_FLAKE_DATE_FORMAT         = 'Y-m-d H:i:s';
    public const LOGGING_HOST                 = 'narvar_log_host';
    public const LOGGING_ERROR_API            = 'narvar_error_api';
    public const LOGGING_DEBUG_API            = 'narvar_debug_api';
    public const LOGGING_HEADER_PLATFORM      = 'logging-platform';
    public const LOGGING_HEADER_STORE         = 'logging-store';
    public const LOGGING_HEADER_RETAILER      = 'logging-retailer-moniker';
    public const LOGGING_PLATFORM_NAME        = 'magento';
     /**
      * Method to return array of class constants.
      *
      * @return array
      */
    public function getConstants()
    {
        $reflectionClass = new \ReflectionClass($this);
        return $reflectionClass->getConstants();
    }
}
