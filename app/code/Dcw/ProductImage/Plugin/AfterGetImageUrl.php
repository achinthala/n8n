<?php

declare(strict_types=1);

namespace Dcw\ProductImage\Plugin;

use Magento\Catalog\Block\Product\Image;
use Magento\Framework\View\DesignInterface;
use Magento\Checkout\Model\Cart as CheckoutCart;
use Dcw\ProductImage\Helper\Data as ProductImageHelper;
use Magento\Catalog\Helper\Image as CatalogImageHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository;
use Psr\Log\LoggerInterface;

class AfterGetImageUrl
{
    public function __construct(
        private readonly ProductImageHelper $imageHelper,
        private readonly DesignInterface $design,
        private readonly CatalogImageHelper $image,
        private readonly CheckoutCart $cart,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger,
        private readonly QuoteRepository $quoteRepository
    ) {
    }

    public function after__call(Image $image, $result, $method)
    {
        try {
            if ($method == 'getImageUrl' && $image->getProductId() > 0) {
                $sampleFlag = 0;

                // This is for Order success page
                $lastRealOrder = $this->cart->getCheckoutSession()->getLastRealOrder();
                $quote = $this->cart->getQuote();
                if ($lastRealOrder->getIncrementId()) {
                    try {
                        $quote = $this->quoteRepository->get($lastRealOrder->getQuoteId());
                    } catch (\Exception $e) {

                    }
                }

                $cartItems = $quote->getAllItems();

                if ($cartItems) {
                    foreach ($cartItems as $item) {
                        if ($item->getProductId()==$image->getProductId()) {
                            $productOptions = $item->getOptionByCode('additional_options');
                            if ($productOptions) {
                                $additionalOptions = json_decode($productOptions->getValue(), true);
                                if (is_array($additionalOptions)) {

                                    foreach ($additionalOptions as $key => $option) {
                                        if ($key==="configurable_product_image") {
                                            $result = $option['value'].(strpos($option['value'], 'FWEBP') == false ? '/-FWEBP' : '');
                                            $sampleFlag = 1;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if ($sampleFlag == 0) {
                    $theme = $this->design->getDesignTheme();
                    $themeName = $theme->getCode();

                    if ($themeName == "Dcw/hyvamobile") {
                        $imagesdimension = $this->imageHelper->getThumbnailImageDimension();
                    } else {
                        $imagesdimension = $this->imageHelper->getSmallImageDimension();
                    }

                    if (!empty($image)) {
                        try {
                            $product = $this->productRepository->getById($image->getProductId());

                            $imagenew = $this->imageHelper->getMainImageUrl($product);

                            if ($imagenew != "") {
                                $result = $imagenew.'/-B'.$imagesdimension.'-FWEBP';
                            } else {
                                $result = $this->image->getDefaultPlaceholderUrl('thumbnail');
                            }
                        } catch (NoSuchEntityException $exception) {
                            $this->logger->error($exception->getTraceAsString());
                        }
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getTraceAsString());
        }

        return $result;
    }
}
