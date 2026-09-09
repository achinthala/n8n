var config = {
    map: {
        '*': {
            weltpixel_ga4_gtm: 'WeltPixel_GA4/js/weltpixel_ga4_gtm',
            weltpixel_ga4_persistentLayer: 'WeltPixel_GA4/js/weltpixel_ga4_persistentlayer'
        }
    },
    config: {
        mixins: {
            'Magento_Swatches/js/swatch-renderer': {
                'WeltPixel_GA4/js/swatch-renderer': true
            },
            'Magento_ConfigurableProduct/js/configurable': {
                'WeltPixel_GA4/js/configurable': true
            }
        }
    }
};
