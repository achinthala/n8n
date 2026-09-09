//@ts-check
define([
  'Magento_Customer/js/model/address-list'
],
function (addressList) {
  "use strict";

  var mixin = {
    /** @inheritdoc */
    initialize: function () {
      this._super();

      addressList.subscribe(function (changes) {
              var self = this;
              changes.forEach(function (change) {
                  if (change.status === 'deleted') {
                    self.rendererComponents = self.rendererComponents.splice(change.index, 0);
                  }
              });
          },
          this,
          'arrayChange'
      );

      return this;
  },
  };

  return function (target) {
      return target.extend(mixin);
  };
});
