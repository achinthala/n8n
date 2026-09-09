<?php
namespace Dcw\SampleProduct\Plugin;

class ConfigProviderPlugin extends \Magento\Framework\Model\AbstractModel
{

    public function afterGetConfig(\Magento\Checkout\Model\DefaultConfigProvider $subject, array $result)
    {
        $items = $result['totalsData']['items'];
        for ($i=0;$i<count($items);$i++) {
            $item_id = $items[$i]['item_id'];
            $options = $items[$i]['options'];
            $custom_options = json_decode($options, 1);
            if (array_key_exists("configurable_product_name",$custom_options)) {
                if (isset($custom_options['configurable_product_name']['value'])) {
                    $items[$i]['name'] = $custom_options['configurable_product_name']['value'];
                    
                }
            }
            if (array_key_exists("configurable_product_image",$custom_options)) {
                if (isset($custom_options['configurable_product_image']['value'])) {
                    $items[$i]['image'] = $custom_options['configurable_product_image']['value'];
                    $items[$i]['productimage']["src"] = $custom_options['configurable_product_image']['value'];
                } else if (isset($custom_options['configurable_product_image']['full_view'])) {
                    $items[$i]['image'] = $custom_options['configurable_product_image']['full_view'];
                    $items[$i]['productimage']["src"] = $custom_options['configurable_product_image']['full_view'];
                }
            }
        }

        $result['totalsData']['items'] = $items;
        return $result;
    }

}