<?php
namespace Dcw\SampleProduct\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item;
use Magento\Checkout\Model\Cart;

class DefaultItemnew
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param Cart $cart
     */

    public function __construct(
        ProductRepositoryInterface $productRepository,
        Cart $cart
    ) {
        $this->productRepo = $productRepository;
        $this->cart = $cart;
    }

    /**
     * Around plugin to get cart item data
     *
     * @param Magento\Checkout\CustomerData\DefaultItem $subject
     * @param Closure $proceed
     * @param Item $item
     *
     * @return html
     */
    public function aroundGetItemData($subject, \Closure $proceed, Item $item)
    {
        $data = $proceed($item);
        $itemId = $item->getItemId();
        $cart = $this->cart;
        $custom_color_option = [];
        $custom_options =[];
        $optionArray=$data['options'];
        foreach($optionArray as $key => $value) {
            if($key=="configurable_product_image") {
                $data['product_image']["src"]=$value['value'];
                $data['product_image']["alt"]=$value['alt'];
               // unset($data['options']["configurable_product_image"]);
            }
            if($key=="configurable_product_name") {
                $data['product_name']=$value['value'];
                unset($data['options']["configurable_product_name"]);
            }
            if($key=="configurable_product_id") {
                $data['configurable_product_id']=$value['value'];
                unset($data['options']["configurable_product_id"]);
            }
            if($key=="configurable_product_url") {
                $data['product_url']=$value['value'];
                unset($data['options']["configurable_product_url"]);
            }
        }
        
        //This return something
        $items = $cart->getQuote()->getAllItems();
        foreach ($items as $item) {
            if ($itemId == $item->getItemId()) {
                $customOptions = $item->getOptionByCode('additional_options');
                if (isset($customOptions)) {
                    if (!$customOptions===null) {
                        $row = $customOptions->getData();
                        $custom_options = json_decode($row['value'], 1);
                        if (isset($custom_options['configurable_product_name']['value'])) {
                            $custom_color_option['sample_color']['label'] = $custom_options['sample_color']['label'];
                            $custom_color_option['sample_color']['value'] = $custom_options['sample_color']['value'];
                        }
                    }
                }
            }
        }
        
        if (isset($custom_options['configurable_product_name']['value'])) {
            $atts = [
                "product_name" => $custom_options['configurable_product_name']['value'],
                "options" => $custom_color_option
            ];
        } else {
            $atts = [];
        }

        return array_merge($data, $atts);
    }
}
