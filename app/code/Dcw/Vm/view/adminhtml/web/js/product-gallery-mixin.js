/**
 * Persist Canto Image Link the same way Alt Text is kept when closing the image dialog.
 */
define([
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    return function (productGallery) {
        $.widget('mage.productGallery', productGallery, {
            /**
             * @inheritdoc
             */
            _initDialog: function () {
                this._super();

                if (!this.$dialog) {
                    return;
                }

                var self = this;

                this.$dialog.on(
                    'input change blur',
                    '[data-role=custom_image_link]',
                    function (event) {
                        self._syncCustomImageLink($(event.currentTarget).val());
                    }
                );

                // Persist before Magento tears down dialog markup (close button / slide-out).
                this.$dialog.on('close', function () {
                    self._persistCustomImageLink();
                });
            },

            /**
             * Load stored Canto URL from gallery item before rendering dialog.
             *
             * @param {Object} imageData
             */
            _showDialog: function (imageData) {
                var $imageContainer = this.findElement(imageData);

                if ($imageContainer && $imageContainer.length) {
                    var storedLink = $imageContainer.find('.custom-image-link-value').val();

                    if (typeof storedLink === 'string') {
                        imageData.custom_image_link = storedLink;
                    }
                }

                return this._super(imageData);
            },

            /**
             * Read textarea value and store on gallery item + imageData.
             */
            _persistCustomImageLink: function () {
                var $field = this.$dialog.find('[data-role=custom_image_link]');

                if ($field.length) {
                    this._syncCustomImageLink($field.val());
                }
            },

            /**
             * @param {string} value
             */
            _syncCustomImageLink: function (value) {
                var imageData = this.$dialog.data('imageData'),
                    $imageContainer = this.$dialog.data('imageContainer'),
                    customImageLink = typeof value === 'string' ? value : '';

                if (!imageData || !$imageContainer || !$imageContainer.length) {
                    return;
                }

                $imageContainer.find('.custom-image-link-value').val(customImageLink);
                imageData.custom_image_link = customImageLink;
            }
        });

        return $.mage.productGallery;
    };
});
