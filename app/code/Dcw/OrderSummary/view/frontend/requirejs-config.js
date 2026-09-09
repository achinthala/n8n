var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/view/summary/abstract-total': {
                'Dcw_OrderSummary/js/view/summary/abstract-total-mixins': true
            }
        }
    },
    map: {
        '*': {
            'Dcw_OrderSummary/product-saving': 'Dcw_OrderSummary/js/view/product-saving',
            'Dcw_OrderSummary/cms-block': 'Dcw_OrderSummary/js/view/cms-block'
        }
    }
};