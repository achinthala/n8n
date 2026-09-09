
# magento2-weltpixel-ga4
Hyvä Themes Compatibility module for WeltPixel_GA4
 
## Installation

### Via packagist.com

Hyvä Compatibility modules that are tagged as stable can be installed using composer via packagist.com:

1. Install via composer
    ```
    composer require hyva-themes/weltpixel-ga4
    ```
2. Enable module
    ```
    bin/magento setup:upgrade
    ```


### Via gitlab

For development of or to contribute to a compatibility module, it needs to be installed using composer via gitlab.  
This installation method is not suited for deployments, because gitlab requires SSH key authorization.

1. Install via composer. If this is the first time a compatibilty module is installed via gitlab, the compat module-fallback repository has to be added as a composer repository.
   This step is only required once.
    ```
   composer config repositories.hyva-themes/magento2-compat-module-fallback git git@gitlab.hyva.io:hyva-themes/magento2-compat-module-fallback.git
   composer require hyva-themes/magento2-compat-module-fallback

    ```
   When the compat-module-fallback repo is configured, the compatibilty module itself can be installed with composer:
    ```
    composer config repositories.hyva-themes/weltpixel-ga4 git git@gitlab.hyva.io:hyva-themes/hyva-compat/weltpixel-ga4.git
    composer require hyva-themes/weltpixel-ga4
    ```
2. Enable module
    ```
    bin/magento setup:upgrade
    ```
