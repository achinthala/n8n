define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push({
        type: 'dcw_po_gateway',
        component: 'Dcw_PurchaseOrderReview/js/view/payment/method-renderer/dcw_po_gateway'
    });

    return Component.extend({});
});
