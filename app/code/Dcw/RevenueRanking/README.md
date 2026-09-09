# RevenueRanking Custom Block

This module provides a comprehensive custom block for displaying revenue ranking information and product counts in various contexts.

## Features

- **Product Count Display**: Shows total product counts in different contexts (category, search, product pages)
- **Revenue Data Integration**: Displays product revenue information, rankings, and buy percentages
- **Filtered Product Counts**: Shows product counts when layered navigation filters are applied
- **Revenue Statistics**: Provides comprehensive revenue statistics and analytics
- **Top Revenue Products**: Displays the highest revenue products
- **Responsive Design**: Mobile-friendly template with responsive styling

## Files Created

### Block Class
- `app/code/Dcw/RevenueRanking/Block/Product/RevenueRankingBlock.php`

### Template
- `app/code/Dcw/RevenueRanking/view/frontend/templates/product/revenue-ranking.phtml`

### Layout Files
- `app/code/Dcw/RevenueRanking/view/frontend/layout/catalog_category_view.xml`
- `app/code/Dcw/RevenueRanking/view/frontend/layout/catalog_product_view.xml`
- `app/code/Dcw/RevenueRanking/view/frontend/layout/catalogsearch_result_index.xml`

### ViewModel
- `app/code/Dcw/RevenueRanking/ViewModel/RevenueRankingViewModel.php`

## Usage Examples

### 1. Basic Usage in Template

```php
<?php
/** @var \Dcw\RevenueRanking\Block\Product\RevenueRankingBlock $block */
?>

<!-- Get total product count -->
<div>Total Products: <?= $block->getTotalProductCount() ?></div>

<!-- Get filtered product count -->
<div>Filtered Products: <?= $block->getFilteredProductCount() ?></div>

<!-- Get current product revenue data -->
<?php if ($block->getCurrentProduct()): ?>
    <?php $revenueData = $block->getProductRevenueData(); ?>
    <div>Revenue: $<?= number_format($revenueData['revenue'], 2) ?></div>
    <div>Ranking: #<?= $revenueData['ranking'] ?></div>
    <div>Buy Percentage: <?= $block->getFormattedBuyPercentage() ?></div>
<?php endif; ?>
```

### 2. Using in Custom Layout

```xml
<referenceContainer name="content">
    <block class="Dcw\RevenueRanking\Block\Product\RevenueRankingBlock" 
           name="custom.revenue.ranking" 
           template="Dcw_RevenueRanking::product/revenue-ranking.phtml">
    </block>
</referenceContainer>
```

### 3. Programmatic Usage

```php
<?php
// In your custom block or controller
$revenueBlock = $this->getLayout()->createBlock(\Dcw\RevenueRanking\Block\Product\RevenueRankingBlock::class);

// Get product count by attribute
$count = $revenueBlock->getProductCountByAttribute('color', 'red');

// Get revenue statistics
$stats = $revenueBlock->getRevenueStatistics();

// Get top revenue products
$topProducts = $revenueBlock->getTopRevenueProducts(10);
?>
```

### 4. Using the ViewModel

```php
<?php
/** @var \Dcw\RevenueRanking\ViewModel\RevenueRankingViewModel $viewModel */
?>

<!-- Get revenue summary -->
<?php $summary = $viewModel->getRevenueSummary(); ?>
<div>Total Products: <?= $summary['total_products'] ?></div>
<div>Products with Revenue Data: <?= $summary['products_with_revenue_data'] ?></div>
<div>Average Revenue: $<?= number_format($summary['average_revenue'], 2) ?></div>

<!-- Check for active filters -->
<?php if ($viewModel->hasActiveFilters()): ?>
    <div>Active Filters: <?= implode(', ', array_keys($viewModel->getActiveFilters())) ?></div>
<?php endif; ?>
```

## Key Methods

### RevenueRankingBlock Methods

- `getTotalProductCount()` - Get total products in current context
- `getFilteredProductCount()` - Get products count with applied filters
- `getProductCountByAttribute($attributeCode, $value)` - Get count for specific attribute value
- `getProductRevenueData($product)` - Get revenue data for a product
- `getProductBuyPercentage($product)` - Calculate buy percentage
- `getFormattedBuyPercentage($product)` - Get formatted buy percentage string
- `getProductRanking($product)` - Get product ranking position
- `hasRevenueData($product)` - Check if product has revenue data
- `getCurrentPageType()` - Get current page type (category, search, product, etc.)
- `getProductCountByCategory($categoryId)` - Get product count for specific category
- `getTopRevenueProducts($limit)` - Get top revenue products
- `getRevenueStatistics()` - Get comprehensive revenue statistics

### RevenueRankingViewModel Methods

- `getProductCountByFilters($filters)` - Get product count with multiple filters
- `getRevenueDataForProducts($productIds)` - Get revenue data for multiple products
- `getProductsWithRevenueData($limit)` - Get products with revenue data in current context
- `getRevenueRanking()` - Get revenue ranking data
- `hasActiveFilters()` - Check if filters are active
- `getActiveFilters()` - Get active filter parameters
- `getRevenueSummary()` - Get revenue summary for current context

## Integration with Layered Navigation

The block automatically detects when layered navigation filters are applied and shows both total and filtered product counts. It includes comprehensive AJAX functionality that updates product counts in real-time when filters are applied.

### AJAX Product Count Updates

The module includes a dedicated AJAX controller and JavaScript functionality that automatically updates product counts when filters are applied via AJAX calls (like with Mirasvit layered navigation).

#### Key Features:
- **Real-time Updates**: Product counts update automatically when filters are applied
- **No Page Refresh**: Counts update via AJAX without requiring page reload
- **Visual Feedback**: Loading indicators and smooth transitions during updates
- **Error Handling**: Retry logic and fallback mechanisms
- **Event System**: Custom events for integration with other components
- **Hyva Theme Support**: Full compatibility with Hyva themes using Alpine.js

#### Files for AJAX Functionality:
- `Controller/Ajax/ProductCount.php` - AJAX controller for product count updates
- `view/frontend/web/js/revenue-ranking-ajax.js` - JavaScript module for AJAX handling (jQuery/RequireJS)
- `etc/frontend/routes.xml` - Route configuration for AJAX endpoints

### Hyva Theme Integration

The module is fully compatible with Hyva themes and uses Alpine.js for dynamic updates instead of jQuery/RequireJS.

#### Hyva-Specific Features:
- **Alpine.js Integration**: Uses Alpine.js `x-data`, `x-text`, and `x-show` directives
- **Modern JavaScript**: Uses `fetch()` API instead of jQuery AJAX
- **Reactive Updates**: Product counts update reactively using Alpine.js data binding
- **Smooth Transitions**: Uses Alpine.js `x-transition` for smooth animations
- **Event System**: Custom events work seamlessly with Alpine.js components

#### Hyva Usage Examples:
```html
<!-- Simple product count with Alpine.js -->
<div x-data="{ totalProducts: 100, filteredProducts: 50 }"
     x-init="document.addEventListener('revenueRanking:productCountUpdated', (e) => {
         totalProducts = e.detail.total_products;
         filteredProducts = e.detail.filtered_products;
     })">
    <span x-text="totalProducts"></span> products
    <span x-show="filteredProducts !== totalProducts" x-transition>
        (<span x-text="filteredProducts"></span> filtered)
    </span>
</div>
```

## Styling

The template includes responsive CSS styling that works well on both desktop and mobile devices. The styling can be customized by overriding the CSS in your theme.

## Performance Considerations

- Product counts are cached to avoid repeated database queries
- Revenue data is fetched efficiently using single queries
- The block is designed to work with Magento's built-in caching mechanisms

## Customization

You can customize the block by:

1. **Overriding the template** in your theme
2. **Extending the block class** to add custom methods
3. **Modifying the layout XML** to change block placement
4. **Adding custom CSS** for styling changes

## Dependencies

- Magento\Catalog
- Magento\Sales
- Dcw\RevenueRanking (existing module)

## CLI Commands

The module includes a command-line interface for managing revenue data:

### Revenue Refresh Command

```bash
# Basic usage - refresh revenue data with default settings
php bin/magento revenue:refresh

# Custom sales period (e.g., 60 days)
php bin/magento revenue:refresh --days=60

# Force refresh even if Buy % is disabled
php bin/magento revenue:refresh --force

# Combined options
php bin/magento revenue:refresh --days=90 --force
```

#### Command Options:
- `--days, -d` - Number of days to look back for revenue calculation (default: 30)
- `--force, -f` - Force refresh even if Buy % feature is disabled

#### Example Output:
```
Starting revenue ranking refresh...
Using sales period: 30 days
Updating revenue data...
Updated 150 revenue records
Updating product attributes...
Updated 150 product attributes
Revenue ranking refresh completed successfully!
```

#### Error Handling:
```
Starting revenue ranking refresh...
Buy % feature is disabled. Use --force to override.
```

## Installation

1. Ensure the files are placed in the correct directories
2. Run `bin/magento setup:upgrade`
3. Run `bin/magento cache:flush`
4. The block will automatically appear on category, product, and search pages

## Troubleshooting

### General Issues
- If the block doesn't appear, check that the layout XML files are in the correct location
- If revenue data is not showing, ensure the RevenueRanking module is properly configured
- For performance issues, check that the revenue_ranking table has proper indexes

### AJAX Product Count Issues

#### Product counts not updating when filters are applied:
1. **Check browser console** for JavaScript errors
2. **Verify AJAX endpoint** is accessible: `/revenueranking/ajax/productcount`
3. **Check network tab** to see if AJAX requests are being made
4. **Ensure Mirasvit module** is properly configured for AJAX filtering

#### "Anchor rel value: undefined" error:
This error occurs when Mirasvit layered navigation tries to set a rel attribute with an undefined value. The module includes automatic fixes for this issue:

1. **Template-level fix**: The templates now check for undefined rel values before outputting them
2. **JavaScript fix**: Automatic cleanup of undefined rel attributes on page load and after AJAX updates
3. **Hyva compatibility**: Special handling for Hyva themes using Alpine.js

**Files that fix this issue:**
- Updated Mirasvit template files to handle undefined rel values
- JavaScript fixes in the RevenueRanking template
- Standalone JavaScript files for both RequireJS and Hyva themes

#### Common Solutions:
```bash
# Clear cache and recompile
bin/magento cache:flush
bin/magento setup:di:compile
bin/magento setup:static-content:deploy

# Check if routes are properly configured
bin/magento cache:status
```

#### Debug AJAX Requests:
```javascript
// Add this to browser console to debug
$(document).on('revenueRanking:productCountUpdated', function(event, data) {
    console.log('Product count updated:', data);
});

// Check if AJAX endpoint is working
$.ajax({
    url: '/revenueranking/ajax/productcount',
    type: 'POST',
    data: { isAjax: '1' },
    success: function(response) {
        console.log('AJAX test successful:', response);
    },
    error: function(xhr, status, error) {
        console.error('AJAX test failed:', error);
    }
});
```

#### Integration with Custom Templates:
If you're integrating with custom product list templates, make sure to include the proper CSS classes:

```html
<!-- Add these classes to your product count elements -->
<span class="product-count-total"><?= $totalProducts ?></span>
<span class="product-count-filtered"><?= $filteredProducts ?></span>
```

### Hyva-Specific Troubleshooting

#### Alpine.js not working:
1. **Check if Alpine.js is loaded**: Ensure Alpine.js is included in your Hyva theme
2. **Verify x-data attribute**: Make sure the `x-data` attribute is properly set
3. **Check browser console**: Look for Alpine.js initialization errors

#### Product counts not updating in Hyva:
1. **Check Alpine.js directives**: Ensure `x-text` and `x-show` are properly used
2. **Verify event listeners**: Check if `revenueRanking:productCountUpdated` events are being dispatched
3. **Test Alpine.js functionality**: Add `x-text="'Alpine.js is working'"` to test basic functionality

#### Debug Alpine.js in Hyva:
```javascript
// Add this to browser console to debug Alpine.js
document.addEventListener('revenueRanking:productCountUpdated', (event) => {
    console.log('Hyva: Product count updated:', event.detail);
});

// Check if Alpine.js is loaded
console.log('Alpine.js loaded:', typeof Alpine !== 'undefined');

// Test Alpine.js component
Alpine.data('testComponent', () => ({
    message: 'Alpine.js is working',
    init() {
        console.log('Alpine.js component initialized');
    }
}));
```
