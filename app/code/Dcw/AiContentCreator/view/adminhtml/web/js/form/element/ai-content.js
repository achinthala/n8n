define([
    'jquery',
    'uiComponent',
    'uiRegistry',
    'mage/translate',
    'Magento_Ui/js/modal/alert'
], function ($, Component, registry, $t, uiAlert) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Dcw_AiContentCreator/form/element/ai-content',
            generateUrl: '',
            productId: 0,
            storeId: 0,
            defaultProvider: 'openai',
            attributeOptions: [],
            providerOptions: [],
            selectedAttribute: '',
            selectedProvider: '',
            selectedMode: 'generate',
            extraInstructions: '',
            draftContent: '',
            isLoading: false,
            statusMessage: ''
        },

        /**
         * @returns {Object}
         */
        initObservable: function () {
            this._super().observe([
                'selectedAttribute',
                'selectedProvider',
                'selectedMode',
                'extraInstructions',
                'draftContent',
                'isLoading',
                'statusMessage'
            ]);

            if (!this.selectedAttribute() && this.attributeOptions.length) {
                this.selectedAttribute(this.attributeOptions[0].value);
            }
            if (!this.selectedProvider()) {
                this.selectedProvider(this.defaultProvider || 'openai');
            }

            return this;
        },

        /**
         * Read current product form field values for prompt context.
         *
         * @returns {Object}
         */
        getCurrentValues: function () {
            var values = {},
                source = this.getFormDataSource();

            if (!source) {
                return values;
            }

            (this.attributeOptions || []).forEach(function (opt) {
                var code = opt.value,
                    path = 'data.product.' + code,
                    value = source.get(path);

                if (value !== undefined && value !== null) {
                    values[code] = String(value);
                } else {
                    values[code] = '';
                }
            });

            return values;
        },

        /**
         * @returns {Object|null}
         */
        getFormDataSource: function () {
            return registry.get('product_form.product_form_data_source');
        },

        /**
         * Request AI draft for the selected attribute.
         */
        generate: function () {
            var self = this,
                attribute = this.selectedAttribute();

            if (!attribute) {
                uiAlert({ content: $t('Please select a content field.') });
                return;
            }
            if (!this.productId) {
                uiAlert({ content: $t('Save the product once before generating AI content.') });
                return;
            }

            this.isLoading(true);
            this.statusMessage($t('Generating…'));

            $.ajax({
                url: this.generateUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY,
                    product_id: this.productId,
                    store_id: this.storeId,
                    attribute_code: attribute,
                    mode: this.selectedMode(),
                    provider: this.selectedProvider(),
                    extra_instructions: this.extraInstructions(),
                    current_values: JSON.stringify(this.getCurrentValues())
                }
            }).done(function (response) {
                if (response && response.success) {
                    self.draftContent(response.content || '');
                    self.statusMessage(
                        response.message || $t('Draft ready. Review, edit, then Apply Draft.')
                    );
                } else {
                    self.statusMessage('');
                    uiAlert({
                        content: (response && response.message)
                            ? response.message
                            : $t('AI generation failed.')
                    });
                }
            }).fail(function () {
                self.statusMessage('');
                uiAlert({
                    content: $t('AI generation request failed. Check network, API credentials, and Cloud egress.')
                });
            }).always(function () {
                self.isLoading(false);
            });
        },

        /**
         * Populate product form field only — does not save the product.
         */
        applyDraft: function () {
            var attribute = this.selectedAttribute(),
                content = this.draftContent(),
                source = this.getFormDataSource(),
                path;

            if (!attribute) {
                uiAlert({ content: $t('Please select a content field.') });
                return;
            }
            if (content === undefined || content === null) {
                uiAlert({ content: $t('No draft content to apply.') });
                return;
            }
            if (!source) {
                uiAlert({ content: $t('Product form data source not found.') });
                return;
            }

            path = 'data.product.' + attribute;
            source.set(path, content);
            this.tryUpdateFieldComponent(attribute, content);

            this.statusMessage(
                $t('Draft applied to the form field. Click Save on the product to persist changes.')
            );
        },

        /**
         * Best-effort sync of visible UI field / WYSIWYG value.
         *
         * @param {String} attribute
         * @param {String} content
         */
        tryUpdateFieldComponent: function (attribute, content) {
            var candidates = [
                'product_form.product_form.content.' + attribute,
                'product_form.product_form.content.container_' + attribute + '.' + attribute,
                'product_form.product_form.search-engine-optimization.' + attribute,
                'product_form.product_form.search-engine-optimization.container_' + attribute + '.' + attribute,
                'product_form.product_form.product-details.' + attribute,
                'product_form.product_form.product-details.container_' + attribute + '.' + attribute
            ];

            candidates.forEach(function (name) {
                registry.get(name, function (component) {
                    if (component && typeof component.value === 'function') {
                        component.value(content);
                    }
                });
            });

            if (window.tinyMCE && typeof window.tinyMCE.get === 'function') {
                var editor = window.tinyMCE.get(attribute);

                if (editor && typeof editor.setContent === 'function') {
                    editor.setContent(content);
                }
            }
        },

        clearDraft: function () {
            this.draftContent('');
            this.statusMessage('');
        }
    });
});
