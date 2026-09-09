/**
 * This will render CMS block content
 */
define([
    'uiComponent',
    'ko'
], function (Component, ko) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Dcw_OrderSummary/cms-block'
        },

        // Load savings value from window.checkoutConfig
        initialize: function () {
            this._super();
            this.cmsblock = ko.observable(window.checkoutConfig.cms_block || '');
        }
    });
});
