<?php
declare(strict_types=1);

namespace Dcw\FlooringCalculation\Controller\Index;

use Exception;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Model\Session;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

class CartPost extends Action
{
    public function __construct(
        Context $context,
        private readonly FormKey $formKey,
        private readonly Cart $cart,
        private readonly ProductFactory $productFactory,
        private readonly SerializerInterface $serializer,
        private readonly JsonFactory $resultJsonFactory,
		private readonly ScopeConfigInterface $scopeConfig,
		private readonly Session $customerSession
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        $mainProductId = $this->getRequest()->getParam('main_product_id');
        $mainProduct = $this->productFactory->create()->load($mainProductId);
        $selectedOptionChildId = $this->getRequest()->getParam('selected_option_child_id');
        $selectedOptionChild = $this->productFactory->create()->load($selectedOptionChildId);

		if(!$this->customerSession->isLoggedIn() && ($mainProduct->getIncstoresFreeProduct() == 1 || $selectedOptionChild->getIncstoresFreeProduct() == 1)) {
		   $this->messageManager->addErrorMessage(__('Free T-Shirt product not available to add to cart!'));
           $response = $resultJson->setData(['success' => 0]);
		   return $response;
		}

        $productFloorType = $this->getRequest()->getParam('product_floor_type');
        $isProductAdded = $isProductUpdated = 0;

        if ($productFloorType == 'Trailer Rolls') {
			if ($this->getRequest()->getParam('customrool_length') &&
				$this->getRequest()->getParam('customrool_length')> 0) {
                try {
                    $mainProductId = $this->getRequest()->getParam('main_product_id');
                    $params = $this->getRequest()->getParams();

                    $additionalOptions = [];

                    $additionalOptions['product_option'] = [
                        'label' => 'Custom Roll Length',
                        'value' => $this->getRequest()->getParam('customrool_length')
                    ];
                    $quote = $this->cart->getQuote();
                    $this->removeEditedQuoteLineIfPossible((int)$mainProductId);
                    $quote = $this->cart->getQuote();
                    $items = $quote->getAllVisibleItems();
                    /**
                     * Also removes a line whose simple_product matches selected_option_child_id
                     * when "item" was not sent (same variant qty update).
                     */
                    $finalquoteqty = 0;
                    if (count($items) > 0) {
                        $itemId = "";
                        $maxsaleqty = $this->getConfigValue('cataloginventory/item_options/max_sale_qty');
                        foreach ($items as $item) {
                            if ($simple_product = $item->getOptionByCode('simple_product')) {
                                    $simple_productvalue = $simple_product->getValue();
                                if($simple_productvalue == $selectedOptionChildId){
                                    $finalquoteqty = $this->getRequest()->getParam('qty')+ $item->getQty();
                                    if($finalquoteqty < $maxsaleqty){
                                        $itemId = $item->getItemId();
                                        $quote->removeItem($itemId)->save();
                                    }
                                }
                            }
                        }
                    }

                    if($finalquoteqty > 0){
                        $isProductUpdated = 1;
                    } else {
                        $isProductAdded = 1;
                    }

                    $this->cart->addProduct($mainProduct, $params);
                    $this->cart->save();

                } catch (Exception $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                    return  $response = $resultJson->setData(['success' => 0,'message'=>$e->getMessage()]);
                }
            }

            if ($isProductAdded == 1) {
                $this->messageManager->addSuccessMessage(__($mainProduct->getName().' Added Successfully.'));
                $response = $resultJson->setData(['success' => 1]);
            } elseif ($isProductUpdated == 1) {
                $this->messageManager->addSuccessMessage(__($mainProduct->getName().' Updated Successfully.'));
                $response = $resultJson->setData(['success' => 1]);
            } else {
                $this->messageManager->addErrorMessage(__('Cannot Add Product to cart.'));
                $response = $resultJson->setData(['success' => 0]);
            }
        } elseif ($productFloorType == 'Carpet Tiles' || $productFloorType == 'Tire Price') {
            $response = $this->addProductToCart();
        } elseif ($productFloorType == 'CBC Tiles') {
        /*=============== product_floor_type -> CBC Tiles START ======================*/
            try {
                $this->removeEditedQuoteLineIfPossible((int)$mainProductId);
                /* ----------------- corner product ----------------------- */
                if ($this->getRequest()->getParam('corner_product_id') &&
                $this->getRequest()->getParam('corner_tiles_qty')> 0) {
                    $additionalOptions = [];
                    $corner_product_id = $this->getRequest()->getParam('corner_product_id');
                    $corner_product = $this->productFactory->create()->load($corner_product_id);
                    $params = [
                        'form_key' => $this->formKey->getFormKey(),
                        'product' => $corner_product_id,
                        'qty'  => $this->getRequest()->getParam('corner_tiles_qty'),
                    ];

                    $additionalOptions['flooring_color'] = [
                        'label' => 'Color',
                        'value' => $selectedOptionChild->getAttributeText('incstores_pim_color_axis')
                    ];
                    $parts = explode('_', $corner_product->getSku());
                    $cbc_type = end($parts);

					$additionalOptions['cbc_type'] = [
                        'label' => 'CBC Type',
                        'value' => ucfirst($cbc_type)
                    ];
                    $additionalOptions['configurable_product_url'] = [
                        'label' => 'Configurable Product Url',
                        'value' => $mainProduct->getProductUrl()
                    ];

                    //$corner_product->setPrice(round($this->getRequest()->getParam('price_per_tile'), 2));
                    $corner_product->addCustomOption(
                        'additional_options',
                        $this->serializer->serialize($additionalOptions)
                    );

				    $quote = $this->cart->getQuote();
				    $items = $quote->getAllVisibleItems();
                    $finalquoteqty = 0;
					if (count($items) > 0) {
						$itemId = "";
						$maxsaleqty = $this->getConfigValue('cataloginventory/item_options/max_sale_qty');
						foreach ($items as $item) {
							if ($simple_product = $item->getOptionByCode('simple_product')) {
									$simple_productvalue = $simple_product->getValue();
								if($simple_productvalue == $selectedOptionChildId){
									$finalquoteqty = $this->getRequest()->getParam('qty')+ $item->getQty();
									if($finalquoteqty < $maxsaleqty){
										$itemId = $item->getItemId();
										$quote->removeItem($itemId)->save();
									}
								}
							}
						}
					}

                    $this->cart->addProduct($corner_product, $params);
                    if($finalquoteqty > 0){
                        $isProductUpdated = 1;
                    } else {
                        $isProductAdded = 1;
                    }
                }
                /* -------------------- corner product -------------------- */

                /* ------------------- border product -------------------- */
                if ($this->getRequest()->getParam('border_product_id') &&
                $this->getRequest()->getParam('border_tiles_qty')> 0) {
                    $additionalOptions = [];
                    $border_product_id = $this->getRequest()->getParam('border_product_id');
                    $border_product = $this->productFactory->create()->load($border_product_id);
                    $params = [
                        'form_key' => $this->formKey->getFormKey(),
                        'product' => $border_product_id,
                        'qty'  => $this->getRequest()->getParam('border_tiles_qty')
                    ];

                    $additionalOptions['flooring_color'] = [
                        'label' => 'Color',
                        'value' => $selectedOptionChild->getAttributeText('incstores_pim_color_axis')
                    ];
					$parts = explode('_', $border_product->getSku());
                    $cbc_type = end($parts);

					$additionalOptions['cbc_type'] = [
                        'label' => 'CBC Type',
                        'value' => ucfirst($cbc_type)
                    ];
                    $additionalOptions['configurable_product_url'] = [
                        'label' => 'Configurable Product Url',
                        'value' => $mainProduct->getProductUrl()
                    ];
                    //$border_product->setPrice(round($this->getRequest()->getParam('price_per_tile'), 2));
                    $border_product->addCustomOption(
                        'additional_options',
                        $this->serializer->serialize($additionalOptions)
                    );

				    $quote = $this->cart->getQuote();
				    $items = $quote->getAllVisibleItems();
                    $finalquoteqty = 0;
					if (count($items) > 0) {
						$itemId = "";
						$maxsaleqty = $this->getConfigValue('cataloginventory/item_options/max_sale_qty');
						foreach ($items as $item) {
							if ($simple_product = $item->getOptionByCode('simple_product')) {
									$simple_productvalue = $simple_product->getValue();
								if($simple_productvalue == $selectedOptionChildId){
									$finalquoteqty = $this->getRequest()->getParam('qty')+ $item->getQty();
									if($finalquoteqty < $maxsaleqty){
										$itemId = $item->getItemId();
										$quote->removeItem($itemId)->save();
									}
								}
							}
						}
					}

					$this->cart->addProduct($border_product, $params);
                    if($finalquoteqty > 0){
                        $isProductUpdated = 1;
                    } else {
                        $isProductAdded = 1;
                    }
                }
                /* ----------------------- border product ------------------------- */

                /* -------------------------- center product ------------------------- */
                if ($this->getRequest()->getParam('center_product_id') &&
                $this->getRequest()->getParam('center_tiles_qty')> 0) {
                    $additionalOptions = [];
                    $center_product_id = $this->getRequest()->getParam('center_product_id');
                    $center_product = $this->productFactory->create()->load($center_product_id);
                    $params = [
                        'form_key' => $this->formKey->getFormKey(),
                        'product' => $center_product_id,
                        'qty'  => $this->getRequest()->getParam('center_tiles_qty')
                    ];

                    $additionalOptions['flooring_color'] = [
                        'label' => 'Color',
                        'value' => $selectedOptionChild->getAttributeText('incstores_pim_color_axis')
                    ];
                    $parts = explode('_', $center_product->getSku());
                    $cbc_type = end($parts);

					$additionalOptions['cbc_type'] = [
                        'label' => 'CBC Type',
                        'value' => ucfirst($cbc_type)
                    ];
					$additionalOptions['configurable_product_url'] = [
                        'label' => 'Configurable Product Url',
                        'value' => $mainProduct->getProductUrl()
                    ];

                    //$center_product->setPrice(round($this->getRequest()->getParam('price_per_tile'), 2));
                    $center_product->addCustomOption(
                        'additional_options',
                        $this->serializer->serialize($additionalOptions)
                    );

				   $quote = $this->cart->getQuote();
				   $items = $quote->getAllVisibleItems();
                   $finalquoteqty = 0;
					if (count($items) > 0) {
						$itemId = "";
						$maxsaleqty = $this->getConfigValue('cataloginventory/item_options/max_sale_qty');
						foreach ($items as $item) {
							if ($simple_product = $item->getOptionByCode('simple_product')) {
									$simple_productvalue = $simple_product->getValue();
								if($simple_productvalue == $selectedOptionChildId){
									$finalquoteqty = $this->getRequest()->getParam('qty')+ $item->getQty();
									if($finalquoteqty < $maxsaleqty){
										$itemId = $item->getItemId();
										$quote->removeItem($itemId)->save();
									}
								}
							}
						}
					}

					$this->cart->addProduct($center_product, $params);
					if($finalquoteqty > 0){
                        $isProductUpdated = 1;
                    } else {
                        $isProductAdded = 1;
                    }
                }
                /* --------------------------- center product --------------------------- */
                $this->cart->save();
                //$this->cart->getQuote()->setTotalsCollectedFlag(false)->collectTotals();
                //$this->cart->getQuote()->save();
                if ($isProductAdded == 1) {
                    $this->messageManager->addSuccessMessage(__($mainProduct->getName().' Added Successfully.'));
                    $response = $resultJson->setData(['success' => 1]);
                } elseif ($isProductUpdated == 1) {
                    $this->messageManager->addSuccessMessage(__($mainProduct->getName().' Updated Successfully.'));
                    $response = $resultJson->setData(['success' => 1]);
                } else {
                    $this->messageManager->addErrorMessage(__('Cannot add product to cart.'));
                    $response = $resultJson->setData(['success' => 0]);
                }
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $response = $resultJson->setData(['success' => 0,'message'=>$e->getMessage()]);
            }
        /*================ product_floor_type -> CBC Tiles END ======================*/
        } else if ($productFloorType == 'None' || $productFloorType == 'Pre Cut Roll') {
            $response = $this->addProductToCart();
        } else {
            $this->messageManager->addErrorMessage(__('Cannot add product to cart.'));
            $response = $resultJson->setData(['success' => 0]);
        }

        return $response;
    }

	public function getConfigValue($variable)
    {
		return $this->scopeConfig->getValue($variable, ScopeInterface::SCOPE_STORE);
	}

    /**
     * Configure flow: remove only the quote line being edited (request param "item").
     * Needed when the customer changes variant on the same row (e.g. Blue → Black): the
     * simple_product removal loop only matches the NEW child id, so the old line would stay.
     * Not used when "item" is absent (normal add to cart).
     */
    private function removeEditedQuoteLineIfPossible(int $mainProductId): void
    {
        if ((int)$this->getRequest()->getParam('is_updating') !== 1) {
            return;
        }
        $quoteItemId = (int)$this->getRequest()->getParam('item');
        if ($quoteItemId <= 0) {
            return;
        }
        $quote = $this->cart->getQuote();
        $quoteItem = $quote->getItemById($quoteItemId);
        if (!$quoteItem || (int)$quoteItem->getQuoteId() !== (int)$quote->getId()) {
            return;
        }
        $lineItem = $quoteItem->getParentItem() ?: $quoteItem;
        if ((int)$lineItem->getProduct()->getId() !== $mainProductId) {
            return;
        }
        $quote->removeItem((int)$lineItem->getId());
        $quote->save();
    }

    public function addProductToCart()
    {
        try {
            $resultJson = $this->resultJsonFactory->create();
            $mainProductId = $this->getRequest()->getParam('main_product_id');
            $mainProduct = $this->productFactory->create()->load($mainProductId);
            $selectedOptionChildId = $this->getRequest()->getParam('selected_option_child_id');
            $selectedOptionChild = $this->productFactory->create()->load($selectedOptionChildId);
            $params = [];
            $params['product'] = $mainProduct->getId();
            $params['qty'] = $this->getRequest()->getParam('qty');
            $options = [];
            $additionalOptions = [];
            $isProductAdded = $isProductUpdated = 0;

            if ($mainProduct->getTypeId() === 'configurable') {
                $productAttributeOptions = $mainProduct->getTypeInstance()->getConfigurableAttributesAsArray($mainProduct);
                foreach ($productAttributeOptions as $option) {
                    $options[$option['attribute_id']] = $selectedOptionChild->getData($option['attribute_code']);
                }
                $params['super_attribute'] = $options;
            }
            $quote = $this->cart->getQuote();
            $this->removeEditedQuoteLineIfPossible((int)$mainProductId);
            $quote = $this->cart->getQuote();
            $items = $quote->getAllVisibleItems();

            $maxsaleqty = $this->getConfigValue('cataloginventory/item_options/max_sale_qty');
            $finalquoteqty = 0;
            if (count($items) > 0) {
                $itemId = "";
                foreach ($items as $item) {
                    if ($simple_product = $item->getOptionByCode('simple_product')) {
                            $simple_productvalue = $simple_product->getValue();
                        if($simple_productvalue == $selectedOptionChildId){
                            $finalquoteqty = $this->getRequest()->getParam('qty')+ $item->getQty();
                            // if($finalquoteqty < $maxsaleqty){
                                $itemId = $item->getItemId();
                                $quote->removeItem($itemId)->save();
                            // }
                        }
                    }
                }
            }

            if($finalquoteqty > 0){
                $params['qty'] = $this->getRequest()->getParam('qty'); //$finalquoteqty;
                $isProductUpdated = 1;
            } else {
                $params['qty'] = $this->getRequest()->getParam('qty');
                $isProductAdded = 1;
            }

            $this->cart->addProduct($mainProduct, $params);
            $this->cart->save();
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return  $response = $resultJson->setData(['success' => 0,'message'=>$e->getMessage()]);
        }
        if ($isProductAdded == 1) {
            $this->messageManager->addSuccessMessage(__($mainProduct->getName().' Added Successfully.'));
            $response = $resultJson->setData(['success' => 1]);
        } elseif ($isProductUpdated == 1) {
            $this->messageManager->addSuccessMessage(__($mainProduct->getName().' Updated Successfully.'));
            $response = $resultJson->setData(['success' => 1]);
        } else {
            $this->messageManager->addErrorMessage(__('Cannot Add Product to cart.'));
            $response = $resultJson->setData(['success' => 0]);
        }

        return $response;
    }
}
