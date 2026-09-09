<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dcw\ProductImage\Controller\Ajax;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\PageCache\Model\Config;
use Magento\Swatches\Helper\Data;

/**
 * Provide product media data.
 */
class Media extends \Magento\Swatches\Controller\Ajax\Media
{
    /**
     * @var \Magento\Catalog\Model\Product Factory
     */
    protected $productModelFactory;

    /**
     * @var \Magento\Swatches\Helper\Data
     */
    private $swatchHelper;

    /**
     * @var \Magento\PageCache\Model\Config
     */
    protected $config;
	
	protected $imagehelper;

    /**
     * @param Context $context
     * @param \Magento\Catalog\Model\ProductFactory $productModelFactory
     * @param \Magento\Swatches\Helper\Data $swatchHelper
     * @param \Magento\PageCache\Model\Config $config
     */
    public function __construct(
        Context $context,
        ProductFactory $productModelFactory,
        Data $swatchHelper,
        Config $config,
		\Dcw\ProductImage\Helper\Data $imagehelper
    ) {
        $this->productModelFactory = $productModelFactory;
        $this->swatchHelper = $swatchHelper;
        $this->config = $config;
		$this->imagehelper = $imagehelper;
		parent::__construct($context, $productModelFactory, $swatchHelper, $config);
    }

    /**
     * Get product media for specified configurable product variation
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $productMedia = [];

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        /** @var \Magento\Framework\App\ResponseInterface $response */
        $response = $this->getResponse();

        if ($productId = (int)$this->getRequest()->getParam('product_id')) {
            /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
            $product = $this->productModelFactory->create()->load($productId);
            $productMedia = [];
			$image = $this->imagehelper->getMainImageUrl($product);
            if ($product->getId() && $product->getStatus() == Status::STATUS_ENABLED && $image!="") {
                $productMedia = $image;
            }
            $resultJson->setHeader('X-Magento-Tags', implode(',', $product->getIdentities()));

            $response->setPublicHeaders($this->config->getTtl());
        }
        $resultJson->setData($productMedia);

        return $resultJson;
    }
}
