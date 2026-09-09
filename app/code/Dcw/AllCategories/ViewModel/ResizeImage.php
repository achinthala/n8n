<?php
namespace Dcw\AllCategories\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Image\AdapterFactory;

class ResizeImage implements ArgumentInterface
{
    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var File
     */
    protected $ioFile;

    /**
     * @var AdapterFactory
     */
    protected $imageFactory;

    /**
     * ResizeImage constructor.
     * @param ImageHelper $imageHelper
     * @param StoreManagerInterface $storeManager
     * @param Filesystem $filesystem
     * @param File $ioFile
     * @param AdapterFactory $imageFactory
     */
    public function __construct(
        ImageHelper $imageHelper,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        File $ioFile,
        AdapterFactory $imageFactory
    ) {
        $this->imageHelper = $imageHelper;
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
        $this->ioFile = $ioFile;
        $this->imageFactory = $imageFactory;
    }

    /**
     * @param $imagePath
     * @param $width
     * @param $height
     * @return string
     */
    public function resizeCustomImage($imagePath, $width, $height)
    {
        // Define the base media path and URL
        $mediaBasePath = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA)->getAbsolutePath();

        // Check if the imagePath contains /media/ and remove it
        if (strpos($imagePath, '/media/') !== false) {
            $imagePath = str_replace('/media/', '', $imagePath);
        }

        // Ensure the imagePath doesn't start with a slash
        $imagePath = ltrim($imagePath, '/');

        // Define the path to the original image
        $originalImagePath = $mediaBasePath . '/' . $imagePath;

        if (!$this->ioFile->fileExists($originalImagePath)) {
            return $this->imageHelper->getDefaultPlaceholderUrl();
        }

        $resizedImagePath = $mediaBasePath . 'resized/'. $width . 'x' . $height . '/' . $imagePath;

        // Check if the resized image already exists
        if (!$this->ioFile->fileExists($resizedImagePath)) {
            // Create the resized image
            $image = $this->imageFactory->create();
            $image->open($originalImagePath);
            $image->constrainOnly(true);
            $image->keepAspectRatio(true);
            $image->keepTransparency(true);
            $image->resize($width, $height);
            $image->save($resizedImagePath);
        }

        // Return the URL of the resized image
        return $this->getMediaImageUrl(). '/resized/'. $width . 'x' . $height . '/' . $imagePath;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getMediaImageUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }
}
