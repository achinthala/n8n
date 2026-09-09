
var config = {
    map: {
        '*': {
            "Magento_Tax/template/checkout/summary/tax": 'Dcw_IncstoreShipping/template/checkout/summary/tax'
        }
    },
    config: {
        mixins: {
            'Magento_Tax/js/view/checkout/summary/tax': {
                'Dcw_IncstoreShipping/js/view/checkout/summary/tax/mixin': true
            },
            'Magento_Tax/js/view/checkout/cart/totals/tax': {
                'Dcw_IncstoreShipping/js/view/checkout/summary/tax/mixin': true
            },
            'Magento_Checkout/js/view/estimation': {
                // We can leverage the same login from the tax summary to determine if we have customs
                'Dcw_IncstoreShipping/js/view/checkout/summary/tax/mixin': true
            }
        }
    }
};
