# Dcw CacheWarmer Module

This Magento 2 module provides functionality to warm cache for products and categories, with a focus on configurable products. It includes automatic cache warming after order placement and product saves, as well as URL generation for cache warming purposes.

## Features

- **Automatic Cache Warming**: Automatically warms cache for configurable products when orders are placed or products are saved
- **URL Generation**: Generates a text file containing all active configurable product URLs for cache warming
- **Console Command**: CLI command to manually generate the warmer URLs file
- **Cron Job**: Automated daily generation of warmer URLs file
- **Spotlight URL Queueing**: Queues spotlight URLs from CSV for cache warming
- **Spotlight URL Preservation**: Automatically preserves spotlight URLs when the Amasty FPC queue is cleared (via generate button or other methods)
- **Stock Management Sync**: Syncs `manage_stock` attribute with `incstores_pim_inventory` attribute for simple products

## Installation

1. Ensure the module is properly registered in `app/code/Dcw/CacheWarmer/registration.php`
2. Run the following commands:
   ```bash
   bin/magento module:enable Dcw_CacheWarmer
   bin/magento setup:upgrade
   bin/magento setup:di:compile
   bin/magento cache:flush
   ```

## Configuration

### Cron Job Schedule

The cron job runs daily at 2:00 AM by default. To change the schedule, edit `app/code/Dcw/CacheWarmer/etc/crontab.xml`:

```xml
<schedule>0 2 * * *</schedule>
```

Cron schedule format: `minute hour day month weekday`
- `0 2 * * *` = 2:00 AM daily
- `0 3 * * *` = 3:00 AM daily
- `0 0 * * *` = Midnight daily

## Usage

### Console Command

Generate the warmer URLs file manually:

```bash
bin/magento dcw:cache-warmer:generate-urls
```

Generate for a specific store:

```bash
bin/magento dcw:cache-warmer:generate-urls --store=1
```

### Output File

The generated file is located at:
```
var/export/warmerurls.txt
```

The file contains one URL per line, with all active configurable product URLs.

### Programmatic Usage

You can also call the service method directly in your code:

```php
use Dcw\CacheWarmer\Service\CacheWarmerService;

// In your class constructor
public function __construct(
    CacheWarmerService $cacheWarmerService
) {
    $this->cacheWarmerService = $cacheWarmerService;
}

// Generate URLs for default store
$result = $this->cacheWarmerService->generateWarmerUrlsFile();

// Or for a specific store
$result = $this->cacheWarmerService->generateWarmerUrlsFile(1);

// Result contains:
// [
//     'count' => 1234,  // Number of URLs generated
//     'file_path' => '/path/to/var/export/warmerurls.txt'
// ]
```

## Automatic Features

### Order Placement Cache Warming

When a configurable product is ordered, the module automatically:
- Queues the configurable product's spotlight URL (if available in CSV)
- Queues spotlight URLs for all child simple products
- Adds URLs to the Amasty FPC queue for cache warming

### Product Save Cache Warming

When a product is saved:
- For simple products: Syncs `manage_stock` with `incstores_pim_inventory` attribute
- For all products: Queues spotlight URL if available in CSV

### Spotlight URL Source

The module reads spotlight URLs from:
```
pub/media/import/supplemental_feed.csv
```

### Spotlight URL Preservation

When the Amasty FPC cache warmer queue is cleared (via the "Generate" button in admin or programmatically), the module automatically preserves any URLs that contain "spotlight" in the URL. This ensures that spotlight URLs remain in the queue even after regeneration.

**How it works:**
- A plugin intercepts the `clear()` method call on `QueuePageRepository`
- Before clearing, it queries and stores all URLs containing "spotlight"
- After clearing, it restores those spotlight URLs back to the queue
- All spotlight URLs are logged for monitoring

This feature is automatic and requires no configuration.

## Module Structure

```
app/code/Dcw/CacheWarmer/
├── Console/
│   └── Command/
│       └── GenerateWarmerUrlsCommand.php    # CLI command
├── Cron/
│   └── GenerateWarmerUrls.php                 # Cron job class
├── Observer/
│   ├── OrderPlaceAfter.php                   # Order placement observer
│   └── ProductSaveAfter.php                  # Product save observer
├── Plugin/
│   └── QueuePageRepositoryPlugin.php         # Plugin to preserve spotlight URLs
├── Service/
│   ├── CacheWarmerService.php                # Main service class
│   └── QueueChecker.php                      # Queue checking service
├── etc/
│   ├── crontab.xml                           # Cron configuration
│   ├── di.xml                                # Dependency injection
│   ├── events.xml                            # Event observers
│   └── module.xml                            # Module configuration
└── registration.php                           # Module registration
```

## Dependencies

- Magento_Catalog
- Magento_Sales
- Amasty_Fpc (for cache queue management)

## Logging

The module logs important events and errors. Check logs at:
```
var/log/system.log
```

Log entries are prefixed with `[CacheWarmer]` for easy filtering.

## Troubleshooting

### Cron Job Not Running

1. Ensure Magento cron is running:
   ```bash
   bin/magento cron:run
   ```

2. Check cron schedule:
   ```bash
   bin/magento cron:run --group=dcw_cache_warmer_cron_group
   ```

3. Verify cron configuration:
   ```bash
   bin/magento cron:status
   ```

### File Not Generated

1. Check directory permissions:
   ```bash
   chmod -R 775 var/export
   ```

2. Verify the export directory exists:
   ```bash
   mkdir -p var/export
   ```

3. Check logs for errors:
   ```bash
   tail -f var/log/system.log | grep CacheWarmer
   ```

### No URLs in File

1. Verify you have active configurable products:
   ```sql
   SELECT COUNT(*) FROM catalog_product_entity 
   WHERE type_id = 'configurable' 
   AND status = 1 
   AND visibility != 1;
   ```

2. Check product visibility settings
3. Verify store ID is correct

## Testing

### Test Console Command

```bash
bin/magento dcw:cache-warmer:generate-urls
```

### Test Cron Job Manually

```bash
bin/magento cron:run --group=dcw_cache_warmer_cron_group
```

### Verify Output

```bash
cat var/export/warmerurls.txt
wc -l var/export/warmerurls.txt  # Count lines
```

## Support

For issues or questions, please contact the development team or check the module logs.

## Version

Current version: 1.0.0
