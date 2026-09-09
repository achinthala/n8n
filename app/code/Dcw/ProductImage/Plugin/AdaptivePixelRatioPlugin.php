<?php
declare(strict_types=1);

namespace Dcw\ProductImage\Plugin;

use Dcw\ProductImage\Helper\Data as DcwProductImageHelper;
use Fastly\Cdn\Model\Config;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Image;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Checkout\Model\Cart as CheckoutCart;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class AdaptivePixelRatioPlugin for image ration
 *
 */
class AdaptivePixelRatioPlugin extends Image
{
    public function __construct(
        Context $context,
        private readonly Config $config,
        private readonly ProductMetadataInterface $productMetadata,
        private readonly DcwProductImageHelper $dcwProductImageHelper,
        private readonly CheckoutCart $cart,
        private readonly ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Adjust srcset if required
     *
     * @param Image $subject
     */
    public function beforeToHtml(Image $subject)
    {
        if ($this->config->isImageOptimizationPixelRatioEnabled() !== true) {
            return;
        }

        $srcSet = [];
        $pId = (int)$subject->getData('product_id');
        try {
            $product = $this->productRepository->getById($pId);

            $image =  $this->dcwProductImageHelper->getMainImageUrl($product);
            $imagesdimension = $this->dcwProductImageHelper->getSmallImageDimension(); //->getBaseImageDimension()
            $sampleUrl = '';
            $cartItems = $this->cart->getQuote()->getAllItems();
            if ($cartItems) {
                foreach ($cartItems as $item) {
                    if ($item->getProductId() == $subject->getData('product_id')) {
                        $productOptions = $item->getOptionByCode('additional_options');
                        if ($productOptions) {
                            $additionalOptions = json_decode($productOptions->getValue(), true);
                            if (is_array($additionalOptions)) {
                                foreach ($additionalOptions as $key => $option) {
                                    if ($key==="configurable_product_image") {
                                        $sampleUrl = $option['value'];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($sampleUrl!='') {
                $imageUrl = $sampleUrl;
            } elseif ($image!="" && $sampleUrl=='') {
                $imageUrl = $image.'/-B'.trim($imagesdimension).'-FWEBP';
            } else {
                $imageUrl = $subject->getData('image_url');
            }

            $pixelRatiosArray = $this->config->getImageOptimizationRatios();
            $glue = "";

            if (!empty($imageUrl)) {
                $glue = (strpos($imageUrl ?? '', '?') !== false) ? '&' : '?';
            }

            # Pixel ratios defaults are based on the table from https://mydevice.io/devices/
            # Bulk of devices are 2x however many new devices like Samsung S8, iPhone X etc are 3x and 4x
            foreach ($pixelRatiosArray as $pr) {
                $ratio = 'dpr=' . $pr . ' ' . $pr . 'x';

                if ($glue != "") {
                    $srcSet[] = $imageUrl . $glue . $ratio;
                }
            }

            $srcSet = implode(',', $srcSet);
            $customAttributes = $subject->getCustomAttributes();

            if (version_compare((string)$this->productMetadata->getVersion(), '2.4', '<')) {
                $customAttributes = !empty($customAttributes) ? [$customAttributes] : [];
                $customAttributes[] = 'srcset="' . $srcSet . '"';
                $subject->setData('custom_attributes', implode(' ', $customAttributes));
            } else {
                $customAttributes = $customAttributes ?: [];
                $customAttributes['srcset'] = $srcSet;
                $subject->setData('custom_attributes', $customAttributes);
            }
        } catch (NoSuchEntityException $exception) {
            $this->_logger->error($exception->getTraceAsString(), [$pId]);
        }
    }
}
