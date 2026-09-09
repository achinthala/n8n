<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package AJAX Shopping Cart for Magento 2
 */
namespace Dcw\AmastyCart\Controller\Cart;

use Amasty\Cart\Model\Source\BlockType;
use Amasty\Cart\Model\Source\ConfirmPopup;
use Amasty\Cart\Model\Source\Option;
use Amasty\Cart\Model\Source\Section;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\LocalizedToNormalizedFactory;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

class Add extends \Amasty\Cart\Controller\Cart\Add
{
    public function execute()
    {
        $messageManager = \Magento\Framework\App\ObjectManager::getInstance()->create(\Magento\Framework\Message\ManagerInterface::class);
    
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $message = __('We can\'t add this item to your shopping cart right now. Please reload the page.');
            $messageManager->addError(__('We can\'t add this item to your shopping cart right now. Please reload the page.'));
            return $this->addToCartResponse($message, 0);
        }

        $params = $this->getRequest()->getParams();
        $product = $this->_initProduct();

        /**
         * Check product availability
         */
        if (!$product) {
            $message = __('We can\'t add this item to your shopping cart right now.');
            $messageManager->addError(__('We can\'t add this item to your shopping cart right now.'));
            return $this->addToCartResponse($message, 0);
        }
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$customerSession = $objectManager->get('Magento\Customer\Model\Session');
		$free_tshirt=(int)$product->getData("incstores_free_product");
		if(!$customerSession->isLoggedIn() && $free_tshirt == 1) {
		    $message = __('Free T-Shirt product not available to add to cart!');
            $messageManager->addError(__('Free T-Shirt product not available to add to cart!'));
            return $this->addToCartResponse($message, 0);
		}
		
		
        $this->setProduct($product);

        try {
            if ($this->isShowOptionResponse($product, $params)) {
                return $this->showOptionsResponse($product);
            }

            if (isset($params['qty'])) {
                $filter = $this->localizedToNormalizedFactory->create()
                    ->setOptions(['locale' => $this->localeResolver->getLocale()]);
                $params['qty'] = $filter->filter($params['qty']);
            }
           
            $free_tshirt=(int)$product->getData("incstores_free_product");            
            /* if($free_tshirt==1) {
                $productId=$product->getId();
                $customerSession = $objectManager->get("Magento\Customer\Model\Session"); 
                $customer = $customerSession->getCustomer();
                $customerId = $customerSession->getCustomerId();
                $quoteFactory = $objectManager->create("Magento\Quote\Model\QuoteFactory"); 
                $configurableProductType = $objectManager->create("Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable"); 
                $itemModel = $objectManager->create('Magento\Quote\Model\Quote\Item');
                $quote = $quoteFactory->create()->loadByCustomer($customerId);
                $checkoutSession = $this->getCheckoutSession();
                if($quote) {
                    $items = $quote->getAllItems();
                    foreach ($items as $key => $item) {
                        $id=$item->getProduct()->getId();
                        $confgproduct = $configurableProductType->getParentIdsByChild($id);
                        if ($confgproduct) {
                            if(in_array($productId,$confgproduct)) {
                                $itemId = $item->getItemId();
                                $quote->removeItem($itemId)->save();
                            }                       
                        } 
                    }
                }
            } */ 

			if($free_tshirt==1) {
				$cartModel = $this->getCartModel();
				$quote = $cartModel->getQuote();
				$freeitems = $quote->getAllVisibleItems();
				if (count($freeitems) > 0) {
						$productId=$product->getId();
						$itemId = "";
						foreach ($freeitems as $item) {
							$id=$item->getProductId();
                            $cartProd = $objectManager->create('Magento\Catalog\Model\Product')->load($id);
                            $freeTshirt=(int)$cartProd->getData("incstores_free_product");
                                    if($freeTshirt==1) {
									$itemId = $item->getItemId();
									$quote->removeItem($itemId)->save();
                                    }
						}
					}
				
			}
            $cartModel = $this->getCartModel();
            $related = $this->getRequest()->getParam('related_product');
            $cartModel->addProduct($product, $params);
            if (!empty($related)) {
                $cartModel->addProductsByIds(explode(',', $related));
            }

            $cartModel->save();
            if ($product->getTypeId() === Configurable::TYPE_CODE && isset($params['super_attribute'])) {
                $this->setQuoteProduct($product);
                if ((bool)$this->helper->getModuleConfig('confirm_display/configurable_image')) {
                    $this->_coreRegistry->register(
                        'amasty_cart_conf_product',
                        $this->configurable->getProductByAttributes(
                            $params['super_attribute'],
                            $product
                        )
                    );
                } else {
                    $this->_coreRegistry->register('amasty_cart_conf_product', $product);
                }
            } else {
                $this->setQuoteProduct($product);
                $this->_coreRegistry->register('amasty_cart_conf_product', $product);
            }

            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            if (!$this->getCheckoutSession()->getNoCartRedirect(true)) {
                list($message, $productHasError) = $this->checkErrorMessages($product, $cartModel);

                if ($productHasError) {
                    return $this->showMessages($message);
                } else {
                    $messageManager->addComplexSuccessMessage(
                        'addCartSuccessMessage',
                        [
                            'product_name' => $product->getName(),
                            'cart_url' => $this->getCartUrl(),
                        ]
                    );
                    
                    return $this->addToCartResponse($message, 1);
                }
                
                $messageManager->addComplexSuccessMessage(
                    'addCartSuccessMessage',
                    [
                        'product_name' => $product->getName(),
                        'cart_url' => $this->getCartUrl(),
                    ]
                );
            }
        } catch (LocalizedException $e) {
            return $this->showMessages(nl2br($this->escaper->escapeHtml($e->getMessage())));

        } catch (\Exception $e) {
            $message = __('We can\'t add this item to your shopping cart right now.');
            $message .= $e->getMessage();
            $messageManager->addError(__('We can\'t add this item to your shopping cart right now.'));
            return $this->addToCartResponse($message, 0);
        }
    }

    private function checkErrorMessages(Product $product, CustomerCart $cartModel): array
    {
        $name = sprintf(
            '<a href="%s" title="%s">%s</a>',
            $product->getProductUrl(),
            $product->getName(),
            $product->getName()
        );
        $productHasError = false;
        $message = '';
        $quoteItem = $cartModel->getQuote()->getItemByProduct($product);

        if ($product->getTypeId() !== Grouped::TYPE_CODE
            && (!$quoteItem || $quoteItem->getErrorInfos())
        ) {
            $productHasError = true;
        } else {
            switch ($this->type) {
                case Section::QUOTE:
                    $message = __('%1 has been added to your quote cart', $name);
                    break;
                case Section::CART:
                default:
                    $message = __('%1 has been added to your cart', $name);
            }

            $message = $this->getProductAddedMessage($product, sprintf('<p>%s</p>', $message));
        }

        if ($cartModel->getQuote()->getHasError()) {
            $messages = [];

            foreach ($cartModel->getQuote()->getErrors() as $error) {
                $messages[] = sprintf('<p>%s</p>', $error->getText());
            }

            if ($productHasError) {
                $message .= implode($messages);
            } else {
                $message .= sprintf('<div class="message error">%s</div>', implode($messages));
            }
        }

        return [$message, $productHasError];
    }
    /**
     * @return bool
     */
    private function isMiniPage()
    {
        return $this->helper->getModuleConfig('dialog_popup/confirm_popup') == ConfirmPopup::MINI_PAGE;
    }

    /**
     * @return bool
     */
    private function isProductPageOrAjaxMini()
    {
        return $this->getRequest()->getParam('product_page') == 'true'
            || filter_var($this->getRequest()->getParam('requestAjaxMini'), FILTER_VALIDATE_BOOLEAN);
    }
   /**
     * @param $type
     * @return string
     */
    private function getProductsHtml($type)
    {
        $html = '';
        $product = $this->getProduct();
        if ($product) {
            $this->_productHelper->initProduct($product->getEntityId(), $this);
            $this->layout->createBlock(
                \Magento\Framework\Pricing\Render::class,
                'product.price.render.default',
                ['data' => [
                    'price_render_handle' => 'catalog_product_prices',
                    'use_link_for_as_low_as' => true
                ]]
            );
            $blockName = 'Amasty\Cart\Block\Product\\';
            if ($this->helper->isTargetRuleEnabled()) {
                $blockName .= 'TargetRule\\';
            }
            $block = $this->layout->createBlock(
                $blockName . ucfirst($type),
                'amasty.cart.product_' . $type,
                ['data' => ['cart_type' => $this->type]]
            );
            $block->setProduct($product)->setTemplate("Amasty_Cart::product/list/items.phtml");
            $html = $block->toHtml();
            $refererUrl = $product->getProductUrl();
            $html = $this->replaceUenc($refererUrl, $html);
        }

        return $html;
    }
    /**
     * @param string $refererUrl
     * @param string $item
     * @return string mixed
     */
    private function replaceUenc($refererUrl, $item)
    {
        $currentUenc = $this->urlHelper->getEncodedUrl();
        $newUenc = $this->urlHelper->getEncodedUrl($refererUrl);
        return str_replace($currentUenc, $newUenc, $item);
    }

    /**
     * @return string
     */
    private function getQtyBlockHtml()
    {
        $result = '';
        // if quote product not detected (example: Amasty_Conf matrix used) qty block not displayed
        if ($this->helper->isChangeQty() && $this->getQuoteProduct()) {
            // use quote getItemByProduct function for avoid getting wrong quote item in case
            // with configurable simple with different custom options
            $quoteItem = $this->getCartModel()->getQuote()->getItemByProduct($this->getQuoteProduct());
            if ($quoteItem) {
                $block = $this->layout->getBlock('amasty.cart.qty');
                if (!$block) {
                    $block = $this->layout->createBlock(
                        \Amasty\Cart\Block\Product::class,
                        'amasty.cart.qty',
                        ['data' => []]
                    );
                }
                $quoteItem = $quoteItem->getParentItem() ?: $quoteItem;

                $block->setTemplate('Amasty_Cart::qty.phtml');
                $block->setQty($quoteItem->getQty());
                $quoteItemId = $quoteItem->getData('parent_item_id') ?: $quoteItem->getData('item_id');
                $block->setQuoteItemId($quoteItemId);

                $result = $block->toHtml();
            }
        }

        return $result;
    }

    private function replaceHtmlElements($html, $product)
    {
        /* replace uenc for correct redirect*/
        $currentUenc = $this->urlHelper->getEncodedUrl();
        $refererUrl = $product->getProductUrl();
        $newUenc = $this->urlHelper->getEncodedUrl($refererUrl);

        $html = str_replace($currentUenc, $newUenc, $html);
        $html = str_replace('"swatch-opt"', '"swatch-opt swatch-opt-' . $product->getId() . '"', $html);
        $html = str_replace('spConfig": {"attributes', 'spConfig": {"containerId":"#confirmBox", "attributes', $html);
        $html = str_replace('[data-role=swatch-options]', '#confirmBox [data-role=swatch-options]', $html);

        return $html;
    }
    /**
     * @param Product $product
     */
    private function setQuoteProduct($product)
    {
        $this->quoteProduct = $product;
    }

    /**
     * @return Product|null
     */
    private function getQuoteProduct()
    {
        return $this->quoteProduct;
    }

    /**
     * Returns cart url
     *
     * @return string
     */
    private function getCartUrl()
    {
        return $this->_url->getUrl('checkout/cart', ['_secure' => true]);
    }
}