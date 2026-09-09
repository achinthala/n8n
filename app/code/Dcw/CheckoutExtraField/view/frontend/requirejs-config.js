var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/action/set-shipping-information': {
                'Dcw_CheckoutExtraField/js/action/set-shipping-information-mixin': true
            }
        }
    },
    map: {
        '*': {
          'Magento_Checkout/template/shipping-address/address-renderer/default.html': 
              'Dcw_CheckoutExtraField/template/shipping-address/address-renderer/default.html',
            'Magento_Checkout/template/shipping-information/address-renderer/default.html': 
            'Dcw_CheckoutExtraField/template/shipping-information/address-renderer/default.html'
        }
    }
};