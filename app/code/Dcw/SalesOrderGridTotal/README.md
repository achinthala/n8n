# Dcw_SalesOrderGridTotal Module

## Overview
This Magento 2 module adds a total amount display to the Sales Order Grid in the admin panel. The total amount is calculated based on all filtered orders and is displayed at both the top and bottom of the grid.

## Features
- Displays total amount of all filtered orders in the Sales Order Grid
- Shows total count of filtered orders
- Updates automatically when filters are applied (Purchase Date, Status, etc.)
- Displays totals at both top and bottom of the grid
- Supports multiple currencies
- Calculates totals from backend data for accuracy

## Installation

1. Copy the module to `app/code/Dcw/SalesOrderGridTotal/`

2. Enable the module:
```bash
php bin/magento module:enable Dcw_SalesOrderGridTotal
```

3. Run setup upgrade:
```bash
php bin/magento setup:upgrade
```

4. Clear cache:
```bash
php bin/magento cache:clean
php bin/magento cache:flush
```

5. Deploy static content (if in production mode):
```bash
php bin/magento setup:static-content:deploy
```

## Usage

After installation, navigate to **Sales > Orders** in the admin panel. You will see:
- A total amount display at the top of the grid (above the grid)
- A total amount display at the bottom of the grid (below the grid)

When you apply any filters (such as Purchase Date, Status, etc.), the totals will automatically update to reflect only the filtered orders.

## Technical Details

### Module Structure
- `etc/module.xml` - Module declaration
- `etc/adminhtml/di.xml` - Dependency injection configuration
- `Model/DataProvider/Plugin/GridTotal.php` - Backend plugin to calculate totals
- `view/adminhtml/layout/sales_order_index.xml` - Layout XML to add totals display
- `view/adminhtml/ui_component/sales_order_grid.xml` - UI component configuration
- `view/adminhtml/web/js/grid-total.js` - JavaScript component for total calculation
- `view/adminhtml/web/css/grid-total.css` - Styles for total display
- `view/adminhtml/templates/grid/total.phtml` - Template for total display

### How It Works

1. **Backend Calculation**: The `GridTotal` plugin intercepts the data provider search results and calculates totals from all filtered orders.

2. **Frontend Display**: JavaScript components listen for grid updates and filter changes, then calculate and display totals from visible grid rows.

3. **Dual Calculation**: The module uses both backend calculation (more accurate) and frontend calculation (fallback) to ensure totals are always displayed correctly.

## Requirements
- Magento 2.3.x or higher
- PHP 7.4 or higher

## Support
For issues or questions, please contact the development team.
