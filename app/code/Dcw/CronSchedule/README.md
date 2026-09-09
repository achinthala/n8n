# Dcw CronSchedule Module

This module allows customizing cron job schedules by overriding default Magento cron configurations.

## Current Overrides

### Default Group

- **bulk_cleanup**: Changed from Every Minute (`* * * * *`) to Every 12 Hours (`0 */12 * * *`)
  - Runs at 00:00 (midnight) and 12:00 (noon) daily

- **bulk_mark_incomplete_operations_as_failed**: Changed from Every 10 Minutes (`*/10 * * * *`) to Every 36 Minutes (`*/36 * * * *`)

- **captcha_delete_expired_images**: Changed from Every 10 Minutes (`*/10 * * * *`) to Every 35 Minutes (`*/35 * * * *`)

- **captcha_delete_old_attempts**: Changed from Every 30 Minutes (`*/30 * * * *`) to Every 55 Minutes (`*/55 * * * *`)

- **magento_newrelicreporting_cron**: Changed from Every 2 Minutes (`*/2 * * * *`) to Every 9 Minutes (`*/9 * * * *`)

- **newsletter_send_all**: Changed from Every 5 Minutes (`*/5 * * * *`) to Every 15 Minutes (`*/15 * * * *`)

### Index Group

- **indexer_reindex_all_invalid**: Changed from Every Minute (`* * * * *`) to Every 5 Minutes (`*/5 * * * *`)

- **indexer_update_all_views**: Changed from Every Minute (`* * * * *`) to Every 5 Minutes (`*/5 * * * *`)

### Staging Group

- **staging_apply_version**: Changed from Every Minute (`* * * * *`) to Every 5 Minutes (`*/5 * * * *`)

- **staging_remove_updates**: Changed from Every Minute (`* * * * *`) to Every 5 Minutes (`*/5 * * * *`)

- **staging_synchronize_entities_period**: Changed from Every Minute (`* * * * *`) to Every 5 Minutes (`*/5 * * * *`)

### Amasty Feed Group

- **amfeed_feed_refresh**: Changed from Every 10 Minutes (`*/10 * * * *`) to Every 20 Minutes (`*/20 * * * *`)

- **mf_blogplus_facebook_autopublish**: Changed from Every 5 Minutes (`*/5 * * * *`) to Every 15 Minutes (`*/15 * * * *`)

- **catalog_product_frontend_actions_flush**: Changed from Every Minute (`* * * * *`) to Every 2 Minutes (`*/2 * * * *`)

- **catalog_product_outdated_price_values_cleanup**: Changed from Every Minute (`* * * * *`) to Every 4 Minutes (`*/4 * * * *`)

- **catalog_product_attribute_value_synchronize**: Changed from Every 5 Minutes (`*/5 * * * *`) to Every 8 Minutes (`*/8 * * * *`)

- **flush_preview_quotas**: Changed from Every 2 Minutes (`*/2 * * * *`) to Every 13 Minutes (`*/13 * * * *`)

- **outdated_authentication_failures_cleanup**: Changed from Every Minute (`* * * * *`) to Every 28 Minutes (`*/28 * * * *`)

- **inventory_in_store_pickup_sales_send_order_notified_emails**: Changed from Every Minute (`* * * * *`) to Every 22 Minutes (`*/22 * * * *`)

### Magefan Blog Default Group

- **magefan_blog_cron_resave_existing_posts**: Changed from Every Minute (`* * * * *`) to Every 6 Minutes (`*/6 * * * *`)

### Resync Failed Feeds Data Exporter Group

- **categories_feed_resend_failed_items**: Changed from Every 5 Minutes (`*/5 * * * *`) to Every 7 Minutes (`*/7 * * * *`)

### Catalog Event Group

- **catalog_event_status_checker**: Changed from Every Minute (`* * * * *`) to Every 3 Minutes (`*/3 * * * *`)

### SaaS Data Exporter Group

- **submit_scopes_website_feed**: Changed from Every Minute (`* * * * *`) to Every 6 Minutes (`*/6 * * * *`)

- **submit_scopes_customergroup_feed**: Changed from Every Minute (`* * * * *`) to Every 26 Minutes (`*/26 * * * *`)

### Baseprovider Group

- **avalara_queue_consumer**: Changed from Every 10 Minutes (`*/10 * * * *`) to Every 12 Minutes (`*/12 * * * *`)

### Avatax Group

- **avatax_batch_queue_transactions_response_process**: Changed from Every 15 Minutes (`*/15 * * * *`) to Every 25 Minutes (`*/25 * * * *`)

- **avatax_config_sync**: Changed from Every 15 Minutes (`*/15 * * * *`) to Every 35 Minutes (`*/35 * * * *`)

- **avatax_items_hscode_sync**: Changed from Every 10 Minutes (`*/10 * * * *`) to Every 15 Minutes (`*/15 * * * *`)

- **avatax_new_items_sync**: Changed from Every 10 Minutes (`*/10 * * * *`) to Every 17 Minutes (`*/17 * * * *`)

- **avatax_pending_items_sync**: Changed from Every 10 Minutes (`*/10 * * * *`) to Every 19 Minutes (`*/19 * * * *`)

- **avatax_processqueue**: Changed from Every 5 Minutes (`*/5 * * * *`) to Every 8 Minutes (`*/8 * * * *`)

## Installation

1. Enable the module:
   ```bash
   php bin/magento module:enable Dcw_CronSchedule
   ```

2. Run setup upgrade:
   ```bash
   php bin/magento setup:upgrade
   ```

3. Clear cache:
   ```bash
   php bin/magento cache:flush
   ```

4. Verify the cron schedule has been updated:
   ```bash
   php bin/magento cron:run
   ```

## Adding More Cron Schedule Overrides

To add more cron schedule overrides, edit `etc/crontab.xml` and add additional `<job>` entries following the same pattern.

Example:
```xml
<job name="job_name" instance="Vendor\Module\Cron\ClassName" method="execute">
    <schedule>0 */12 * * *</schedule>
</job>
```

## Cron Schedule Format

The schedule uses standard cron format: `minute hour day month weekday`

- `0 */12 * * *` = Every 12 hours (at minute 0 of hours 0 and 12)
- `0 0 * * *` = Daily at midnight
- `*/5 * * * *` = Every 5 minutes
- `0 2 * * *` = Daily at 2:00 AM
