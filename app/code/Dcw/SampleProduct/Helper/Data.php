<?php

declare(strict_types=1);

namespace Dcw\SampleProduct\Helper;

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\View\LayoutFactory;
use Dcw\SampleProduct\Block\SampleData;
use Psr\Log\LoggerInterface;

class Data extends AbstractHelper
{
    protected $layoutFactory;
    protected $logger;
 
    public function __construct(
        LayoutFactory $layoutFactory,
        LoggerInterface $logger
    ) {
        $this->layoutFactory = $layoutFactory;
        $this->logger = $logger;
    }
    
    public function getTemplate($productId)
    {
        try {
            $layout = $this->layoutFactory->create();
            $blockOption = $layout->createBlock(SampleData::class)
                ->setProductId($productId)
                ->setTemplate("Dcw_SampleProduct::productdetails.phtml");
        
            return $blockOption->toHtml();
        } catch (Exception $e) {
            // Log the exception for debugging purposes (optional)
            $this->logger->critical($e->getMessage());
        }
    }
}
