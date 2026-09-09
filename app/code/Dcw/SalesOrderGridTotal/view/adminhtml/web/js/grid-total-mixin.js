/**
 * Mixin to extend grid listing component.
 * Passes current request params when grid/filters update so grid total uses same filters.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    function paramsToQueryString(params) {
        if (!params || typeof params !== 'object') return '';
        var parts = [];
        if (params.namespace) {
            parts.push('namespace=' + encodeURIComponent(params.namespace));
        }
        if (params.filters && typeof params.filters === 'object') {
            Object.keys(params.filters).forEach(function (k) {
                if (k === 'placeholder') return;
                var v = params.filters[k];
                if (v === '' || v == null) return;
                if (typeof v === 'object' && v !== null && (v.from !== undefined || v.to !== undefined)) {
                    if (v.from) parts.push('filters[' + k + '][from]=' + encodeURIComponent(v.from));
                    if (v.to) parts.push('filters[' + k + '][to]=' + encodeURIComponent(v.to));
                } else {
                    parts.push('filters[' + k + ']=' + encodeURIComponent(String(v)));
                }
            });
        }
        return parts.length ? '?' + parts.join('&') : '';
    }

    return function (target) {
        return target.extend({
            /**
             * Get current request params from listing (filters, etc.)
             */
            getGridTotalParams: function () {
                try {
                    if (this.getRequest && typeof this.getRequest === 'function') {
                        return paramsToQueryString(this.getRequest());
                    }
                    if (this.get && this.get('dataSource')) {
                        var ds = this.get('dataSource');
                        var params = (ds.get && ds.get('params')) || (ds.get && ds.get('data')) || {};
                        if (params && typeof params === 'object') return paramsToQueryString(params);
                    }
                    if (this.request && typeof this.request === 'object') {
                        return paramsToQueryString(this.request);
                    }
                } catch (e) {}
                return '';
            },

            /**
             * Override updateData to trigger total with current params
             */
            updateData: function () {
                var result = this._super();
                var self = this;
                setTimeout(function () {
                    var q = self.getGridTotalParams && self.getGridTotalParams();
                    $(document).trigger('dcwGridTotalUpdate', [q || '']);
                }, 400);
                setTimeout(function () {
                    var q = self.getGridTotalParams && self.getGridTotalParams();
                    $(document).trigger('dcwGridTotalUpdate', [q || '']);
                }, 1000);
                return result;
            },

            /**
             * Override applyFilters to trigger total with current params
             */
            applyFilters: function () {
                var result = this._super();
                var self = this;
                setTimeout(function () {
                    var q = self.getGridTotalParams && self.getGridTotalParams();
                    $(document).trigger('dcwGridTotalUpdate', [q || '']);
                }, 400);
                setTimeout(function () {
                    var q = self.getGridTotalParams && self.getGridTotalParams();
                    $(document).trigger('dcwGridTotalUpdate', [q || '']);
                }, 1000);
                return result;
            }
        });
    };
});
