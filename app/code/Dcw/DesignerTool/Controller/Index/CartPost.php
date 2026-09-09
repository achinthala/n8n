<?php

declare(strict_types=1);

namespace Dcw\DesignerTool\Controller\Index;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Serialize\SerializerInterface;

class CartPost extends \Magento\Framework\App\Action\Action
{
    protected $formKey;
    protected $cart;
    protected $productRepository;
    protected $resultJsonFactory;
    protected $eavConfig;
    protected $serializer;

    public function __construct(
        Context $context,
        FormKey $formKey,
        Cart $cart,
        ProductRepositoryInterface $productRepository,
        JsonFactory $resultJsonFactory,
        EavConfig $eavConfig,
        SerializerInterface $serializer
    ) {
        $this->formKey = $formKey;
        $this->cart = $cart;
        $this->productRepository = $productRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->eavConfig = $eavConfig;
        $this->serializer = $serializer;

        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        $cartItems = $this->getRequest()->getParam('cart_items');
        if (empty($cartItems)) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl('/checkout/cart');
            return $resultRedirect;
        }

        $cartItemsArray = json_decode($cartItems, true);

        try {
            foreach ($cartItemsArray as $cartItem) {
                $productId = $cartItem['product_id'];
                $product = $this->productRepository->getById($productId);

                $color = $product->getAttributeText('incstores_pim_color_axis');
                $variant = $product->getAttributeText('incstores_pim_variant_axis');

                $attributeColor = $this->eavConfig->getAttribute('catalog_product', 'incstores_pim_color_axis');
                $colorLabel = $attributeColor->getStoreLabel();

                $attributeVariant = $this->eavConfig->getAttribute('catalog_product', 'incstores_pim_variant_axis');
                $variantLabel = $attributeVariant->getStoreLabel();

                $options = [];

                if ($color) {
                    $options[] = [
                        'label' => $colorLabel,
                        'value' => $color
                    ];
                }

                if ($variant) {
                    $options[] = [
                        'label' => $variantLabel,
                        'value' => $variant
                    ];
                }

                $params = [
                    'form_key' => $this->formKey->getFormKey(),
                    'product' => $productId,
                    'qty'  => $cartItem['qty'],
                    'additional_options' => $options,
                    'product_id' => $productId
                ];

                $product->addCustomOption(
                    'additional_options',
                    $this->serializer->serialize($options)
                );

                $this->cart->addProduct($product, new \Magento\Framework\DataObject($params));
            }

            $this->cart->save();
            $this->cart->getQuote()->setTotalsCollectedFlag(false)->collectTotals();
            $this->cart->getQuote()->save();

            $response = $resultJson->setData(['success' => 1]);

        } catch (Exception $e) {
            $response = $resultJson->setData(['success' => 0,'message'=>$e->getMessage()]);
        }

        return $response;
    }
}
