<?php

declare(strict_types=1);

namespace Dcw\SampleProduct\Controller\Sample;

use Exception;
use Dcw\ProductImage\Helper\Data as ProductImageData;
use Dcw\SampleProduct\Block\SampleData;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Result\PageFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;

class Products extends Action
{
    const CONFIG_PATH_MAX_SAMPLE_IN_CART = 'dcw_order_sample/order_sample_config/default_max_sample_in_cart';
    const CONFIG_PATH_MAX_SAMPLE_IN_CART_MSG = 'dcw_order_sample/order_sample_config/default_max_sample_in_cart_msg';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly Cart $cart,
        private readonly Product $product,
        private readonly CheckoutSession $checkoutSession,
        private readonly ResultJsonFactory $resultJsonFactory,
        private readonly SerializerInterface $serializer,
        private readonly ProductRepository $productRepository,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly Json $json,
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly SampleData $sampleBlock,
        private readonly AttributeSetRepositoryInterface $attributeSetRepository,
        private readonly ProductImageData $productImageData
    ) {
        parent::__construct($context);
    }

    /**
     * Sample Product add to cart
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        $postparms = $this->getRequest()->getParams();
        $isSampleAdded = 0;

        if (isset($postparms['action_type']) && isset($postparms['pids'])) {
            $idColor = [];
            $sampleInCart = [];
            $productIds = explode(',', $postparms['pids']);
            $spidsColors = [];

            if (!empty($postparms['spidsColors'])) {
                $spidsColors = explode(',', $postparms['spidsColors']);
            }

            $childIds=explode(',', $postparms['childproid']);

            if($postparms['colors'] != "") {
                $colors = explode(',', $postparms['colors']);

                foreach($colors as $color)
                {
                    $colorArray = explode('~', $color);
                    if (isset($colorArray[0], $colorArray[1])) {
                        $idColor[$colorArray[0]] = $colorArray[1];
                    }
                }
            }

            if ($postparms['action_type'] == 2) {
                $quoteItems = $this->checkoutSession->getQuote()->getItemsCollection();

                foreach ($quoteItems as $item) {
                    foreach ($productIds as $productId) {
                        if ($item->getProductId() == $productId) {
                            $this->cart->removeItem($item->getId())->save();
                        }         
                    }
                }
                
                $this->messageManager->addSuccess(__('Sample Product Remove Successfully.'));
            } elseif ($postparms['action_type'] == 1) {
                $storeId = $this->storeManager->getStore()->getStoreId();
                $quoteItems = $this->checkoutSession->getQuote()->getAllItems();
                $countOrderSampleInCart = 0;

                $systemSampleOrderCount = $this->getConfigValue(self::CONFIG_PATH_MAX_SAMPLE_IN_CART, $storeId);
                
                $systemSampleOrderMsg = $this->getConfigValue(self::CONFIG_PATH_MAX_SAMPLE_IN_CART_MSG, $storeId);

                foreach ($quoteItems as $item) {
                    $additionalOptions = $item->getOptionByCode('additional_options');

                    if (!empty($additionalOptions)) {
                        $option_val = $this->json->unserialize($additionalOptions->getValue());

                        if (isset($option_val) && $option_val!="") {
                            foreach ($option_val as $key => $op) {
                                if ($key == "sample_color" && $op['label'] == "Color") {
                                    $optionValue = $op['value'];
                                    $sampleInCart[] = $item->getProductId().'~'.$optionValue;
                                    $countOrderSampleInCart += 1;
                                    break;
                                } 
                            }                     
                        } 
                    }
                }
                                       
                if ($systemSampleOrderCount == "" || $systemSampleOrderCount == "0" || $systemSampleOrderCount > $countOrderSampleInCart) {
                    if (!empty($spidsColors) && count($spidsColors)>0) {
                        if ($postparms['confproid'] != "") {
                            $configprod = $this->productRepository->getById($postparms['confproid']);
                        }

                        $errorIsSampleExist = 0;
                        $cartItems = $quoteItems;

                        foreach ($cartItems as $cartItem) {
                            $cartSampleItem = $cartItem->getProductId().'~'.$this->sampleBlock->getSampleProductColor($cartItem);

                            if (in_array($cartSampleItem,$spidsColors)) {
                                $errorIsSampleExist = 1;
                                $this->messageManager->addError('This product is already available in your cart.');
                                break;
                            }
                        }

                        if ($errorIsSampleExist != 1) {
                            foreach ($spidsColors as $spidColor) {
                                $additionalOptions = [];
                                $params = [];
                                $spidColor_arr = explode('~', $spidColor);
                                $sPid = $spidColor_arr[0];
                                $sColor = '';

                                if(!empty($spidColor_arr[1])) {
                                    $sColor = $spidColor_arr[1];
                                }
                                    
                                if ($systemSampleOrderCount > $countOrderSampleInCart) {
                                    $sProduct = $this->productRepository->getById($sPid);
									$color_display_name=$sProduct->getIncstores_pim_color_display_name();
                                    
                                    $additionalOptions['configurable_product_name'] = [
                                        'label' => 'Product Name',
                                        'value' => "Sample-".$postparms['confproname']
                                    ];

                                    $additionalOptions['configurable_product_id'] = [
                                        'label' => 'Configurable Product Id',
                                        'value' => $postparms['confproid']
                                    ];

                                    if ($configprod) {
                                        $additionalOptions['main_configurable_product_name'] = [
                                            'label' => 'Configurable Product Name',
                                            'value' => $configprod->getName()
                                        ];

                                        $additionalOptions['configurable_product_sku'] = [
                                            'label' => 'Configurable Product SKU',
                                            'value' => $configprod->getSku()
                                        ];

                                        $attributeSetId = $configprod->getAttributeSetId();

                                        $attributeSet = $this->attributeSetRepository->get($attributeSetId);
                                        $attributeSetName = $attributeSet->getAttributeSetName();

                                        $additionalOptions['configurable_product_category'] = [
                                            'label' => 'Configurable Product Category',
                                            'value' => $attributeSetName
                                        ];

                                        $additionalOptions['configurable_product_url'] = [
                                            'label' => 'Configurable Product Url',
                                            'value' => $configprod->getProductUrl()
                                        ];

                                        $cantoImage = $this->productImageData->getMainImageUrl($configprod);
                                        $additionalOptions['main_configurable_product_image'] = [
                                            'label' => 'Main Configurable Product Image',
                                            'value' => $cantoImage ?? ''
                                        ];
                                    }

                                    $additionalOptions['sample_color'] = [
                                        'label' => 'Color',
                                        'value' => $sColor
                                    ];
									
									$additionalOptions['color_display_name'] = [
                                        'label' => 'Color Display Name',
                                        'value' => $color_display_name
                                    ];

                                    $mainImageData = $this->getMainImageData($idColor, $sColor);
                                   
                                    $additionalOptions['configurable_product_image'] = [
                                        'label' => 'Configurable Product Image',
                                        'value' => $mainImageData,
                                        'alt' => $postparms['confproname']
                                    ];

                                    $params = array(
                                        'product' => $sPid,
                                        'name' => $postparms['confproname'],
                                        'qty' => 1
                                    );

                                    if ($sProduct->isSalable()) {
                                        $sProduct->setName($postparms['confproname']);
                                        $sProduct->addCustomOption('additional_options', $this->serializer->serialize($additionalOptions));

                                        try {
                                            $this->cart->addProduct($sProduct, $params);
                                            
                                            $this->messageManager->addSuccess(__('Sample Product Add Successfully.'));
                                            $isSampleAdded = 1;
                                            $countOrderSampleInCart++;
                                        } catch (Exception $e) {
                                            $isSampleAdded = 0;
                                            $this->messageManager->addError(__($e->getMessage()));

                                            $output = [
                                                'is_sample_added' => $isSampleAdded
                                            ];

                                            return $resultJson->setData($output);
                                        }
                                    } else {
                                        $this->messageManager->addError(__('Requested sample product out of stock.'));
                                    }                 
                                } else {
                                    $this->messageManager->addError(__($systemSampleOrderMsg));
                                }
                            }

                            $this->cart->save();
                        }
                    } else {
                        $this->messageManager->addError(__('No sample product selected'));
                    }  
                } else {
                    $this->messageManager->addError(__($systemSampleOrderMsg));
                }    
            }
        }

        $output = [
            'is_sample_added' => $isSampleAdded
        ];

        return $resultJson->setData($output);
    }

    public function getMainImageData($idColor, $sColor)
    {
        $mainImageData = "";

        foreach($idColor as $key => $value) {
            if($sColor == $value) {
                $child_product = $this->productRepository->getById($key);
                $mainImageData = $child_product->getIncstoresPimSwatchImageSkuUrl1();
            }
        }

        return $mainImageData;
    }

    public function getConfigValue($path, $storeId)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
