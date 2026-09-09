<?php
declare(strict_types=1);

namespace Dcw\ProductImage\Plugin;

use Dcw\ProductImage\Helper\Data;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Psr\Log\LoggerInterface;

class AfterGetImage
{
    public function __construct(
        private readonly Data $imageHelper,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function afterGetImage(AbstractProduct $subject, $result)
    {
        $productId = (int)$result->getProductId();

        try {
            $product = $this->productRepository->getById($productId);

            $imageUrl = $this->getExternalImage($product);
            $imagesdimension = $this->imageHelper->getSmallImageDimension();
            $image = [];
            $image['image_url'] = $imageUrl . '/-B' . $imagesdimension.'-FWEBP';
            $image['width'] = $imagesdimension;
            $image['label'] = $product->getName();
            $image['ratio'] = "1.25";
            $image['custom_attributes'] = "";
            $image['resized_image_width'] = "399";
            $image['resized_image_height'] = "399";
            $image['product_id'] = $product->getId();

            $result->setData($image);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getTraceAsString());
        }

        return $result;
    }

    public function getExternalImage($product)
    {
        return $this->imageHelper->getMainImageUrl($product);
    }
}
