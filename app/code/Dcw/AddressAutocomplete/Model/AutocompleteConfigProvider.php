<?php


namespace Dcw\AddressAutocomplete\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class AutocompleteConfigProvider implements ConfigProviderInterface
{
    /**
     * @var \Dcw\AddressAutocomplete\Helper\Data
     */
    private $helper;

    /**
     * @param \Dcw\AddressAutocomplete\Helper\Data $helper
     */
    public function __construct(
        \Dcw\AddressAutocomplete\Helper\Data $helper
    ) {

        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config['shipperhq_autocomplete'] = [
            'active'        => $this->helper->getConfigValue('shipping/shipper_autocomplete/active'),
            'api_key'  =>    $this->helper->getConfigValue('shipping/shipper_autocomplete/google_api_key'),
            'use_geolocation'  =>    $this->helper->getConfigValue('shipping/shipper_autocomplete/use_geolocation'),
            'use_long_postcode'  =>    $this->helper->getConfigValue('shipping/shipper_autocomplete/use_long_postcode')
        ];
        return $config;
    }
}
