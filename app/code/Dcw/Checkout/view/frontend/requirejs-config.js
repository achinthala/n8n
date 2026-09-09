var config = {
    map: {
        '*': {
            'Magento_PurchaseOrder/template/checkout/billing-address/details.html':
              'Dcw_Checkout/template/checkout/billing-address/details.html',
            'Magento_Checkout/template/billing-address/details.html':
              'Dcw_Checkout/template/billing-address/details.html',
        }
    },
    config: {
        mixins: {
            'Magento_Ui/js/lib/validation/rules': {
                'Dcw_Checkout/js/validation/dcw-phone-min-digits-mixin': true
            }
        }
    }
};