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
                type: 'splitpayment',
                component: 'Dcw_SplitPayment/js/view/payment/method-renderer/splitpayment'
            }
        );
        return Component.extend({});
    }
);