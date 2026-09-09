<?php
namespace Dcw\DisablePdpWebp\Plugin;

use Magento\Framework\App\RequestInterface;
use Yireo\Webp2\Convertor\Convertor;
use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Image\Image;

class DisableWebpOnPdp
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * Constructor
     *
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Before converting images to WebP, check if it's the PDP.
     * If it's PDP, skip the WebP conversion.
     *
     * @param Convertor $subject
     * @param Image $image
     * @return array|null
     * @throws ConvertorException
     */
    public function beforeConvertImage(Convertor $subject, Image $image)
    {
        // Check if the current page is PDP

        if ($this->isPdp()) {
            // Prevent the WebP conversion on PDP by throwing an exception or returning early
            throw new ConvertorException('WebP conversion is disabled for the PDP.');
        }

        return [$image]; // Proceed with conversion if not PDP
    }

    /**
     * Determine if the current request is a PDP page.
     *
     * @return bool
     */
    private function isPdp()
    {
        return $this->request->getFullActionName() === 'catalog_product_view';
    }
}
