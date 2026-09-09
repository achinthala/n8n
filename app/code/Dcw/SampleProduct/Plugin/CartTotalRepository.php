<?php
declare(strict_types=1);

namespace Dcw\SampleProduct\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;

class CartTotalRepository
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function afterGet(
        \Magento\Quote\Model\Cart\CartTotalRepository $subject,
        $result,
        $cartId
    ) {
        if ($result instanceof \Magento\Quote\Api\Data\TotalsInterface) {
            $items = $result->getItems();
            $resultItems = [];
            foreach ($items as $eachItem) {
                $options = $eachItem->getOptions();
                $customOptions=json_decode($options,true);
                if(isset($customOptions)) {
                    if(isset($customOptions['configurable_product_name'])){
                        $name=$customOptions['configurable_product_name']['value'];
                        $eachItem->setName($name);
                    }
                } 
                if(isset($customOptions['configurable_product_image'])){
                    $image=$customOptions['configurable_product_image']['value'];
                    if(isset($customOptions['configurable_product_image']['full_view'])) {
                        $image=$customOptions['configurable_product_image']['full_view'];
                    }
                    
                    $eachItem->setData('image',$image);
                }

                $resultItems[] = $eachItem;
            }
            $result->setItems($resultItems);
        }
        return $result;
    }
}