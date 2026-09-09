define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'wirepayment',
                component: 'Dcw_WirePayment/js/view/payment/method-renderer/wirepayment'
            }
        );
        return Component.extend({});
    }
);