# Clear Cache Instructions

To fix the "Call to undefined method" error, you need to clear Magento's generated code cache:

## Steps to Fix:

1. **Remove generated code and cache:**
```bash
rm -rf generated/code/*
rm -rf generated/metadata/*
php bin/magento cache:clean
php bin/magento cache:flush
```

2. **Or use Magento commands:**
```bash
php bin/magento setup:di:compile
php bin/magento cache:clean
php bin/magento cache:flush
```

3. **If the issue persists, also clear var/cache:**
```bash
rm -rf var/cache/*
rm -rf var/page_cache/*
rm -rf var/generation/*
```

## Why this happens:

Magento caches interceptor classes in the `generated/code` directory. When you disable a plugin in `di.xml`, the old cached interceptor class may still exist and try to call methods that no longer exist in your plugin class.

After clearing the cache, Magento will regenerate the interceptors based on the current `di.xml` configuration, which no longer includes this plugin.
