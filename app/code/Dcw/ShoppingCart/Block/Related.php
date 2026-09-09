<?php

namespace Dcw\ShoppingCart\Block;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Block\Product\Context;
use Magento\Framework\App\Request\Http;

class Related extends AbstractProduct
{
    protected $cart;
	protected $http;
	protected $_productloader;
    public function __construct(
        Context $context,
        Cart $cart,
		Http $http,
		\Magento\Catalog\Model\ProductFactory $_productloader,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->cart = $cart;
		$this->http = $http;
		$this->_productloader = $_productloader;
    }

    public function getItems()
    {
        $relatedProducts = [];
        foreach ($this->cart->getItems() as $item) {
            $product = $item->getProduct();
            $relatedProductCollection = $product->getRelatedProductCollection()
                ->addAttributeToSelect('*');
			if(count($relatedProductCollection->getData())){
				$relatedProducts[$product->getId()] = $relatedProductCollection;
			}
        }
        return $relatedProducts;
    }
	
	public function getItemCount() {
        return count($this->getItems());
    }
	
	public function getActionName(){
		
		return $this->http->getControllerName();
		
	}
	
	public function getLoadProduct($id)
    {
        return $this->_productloader->create()->load($id);
    }

}
