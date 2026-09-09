var config = {
    map: {
        '*': {
          'Magento_NegotiableQuote/template/shipping.html':
              'Dotcomweavers_OrderRestrictions/template/shipping.html',
          'Magento_NegotiableQuote/template/shipping-address/address-renderer/default.html':
              'Dotcomweavers_OrderRestrictions/template/shipping-address/address-renderer/default_negotiable_quote.html',
          'Magento_Checkout/template/shipping-address/address-renderer/default.html':
              'Dotcomweavers_OrderRestrictions/template/shipping-address/address-renderer/default_checkout.html',
          'Magento_PurchaseOrder/template/checkout/shipping-address/address-renderer/default.html':
              'Dotcomweavers_OrderRestrictions/template/shipping-address/address-renderer/default_purchase_order.html',
        }
  },
  config: {
    mixins: {
      'Magento_Checkout/js/view/shipping-address/address-renderer/default': {
        'Dotcomweavers_OrderRestrictions/js/view/shipping-address/address-renderer-mixin': true
      },
      'Magento_Customer/js/model/customer/address': {
          'Dotcomweavers_OrderRestrictions/js/view/checkout/customer-address-mixin': true
      },
      'Magento_Checkout/js/view/shipping': {
          'Dotcomweavers_OrderRestrictions/js/view/checkout/shipping-mixin': true
      },
      'Magento_Checkout/js/view/shipping-address/list': {
        'Dotcomweavers_OrderRestrictions/js/view/checkout/shipping-address/list-mixin': true
      }
    }
  }
};