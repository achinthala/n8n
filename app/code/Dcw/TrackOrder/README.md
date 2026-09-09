# Dcw_TrackOrder

Simplified guest order lookup - Order ID + Email only.

## Flow

1. Visitor opens Track Your Order page
2. Enters Order ID → Email field appears, Order ID validated on blur
3. Enters Email → Continue button activates
4. Clicks Continue → System matches Order ID + Email, shows order details if valid
5. If mismatch or invalid → Error message displayed on form

## Module Files

- `Plugin/GuestPlugin.php` - Order ID + Email validation on form submit
- `Model/OrderValidator.php` - AJAX order existence check
- `Controller/Order/Validate.php` - JSON endpoint for order validation
- `view/frontend/layout/trackorder_order_validate.xml` - Non-cacheable layout for validate action

## Theme (hyvadesktop)

- `app/design/frontend/Dcw/hyvadesktop/Magento_Sales/templates/guest/form.phtml`
