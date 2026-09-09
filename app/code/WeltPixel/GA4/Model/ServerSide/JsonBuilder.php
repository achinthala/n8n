<?php
namespace WeltPixel\GA4\Model\ServerSide;

class JsonBuilder extends \Magento\Framework\Model\AbstractModel
{

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;

    /** @var string  */
    const CACHE_PATH = 'ga4_cache';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file
    )
    {
        parent::__construct($context, $registry);
        $this->fileSystem = $fileSystem;
        $this->directoryList = $directoryList;
        $this->file = $file;
    }


    /**
     * @param $content
     * @return false|string|null
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function saveToFile($content)
    {
        $cachePath = $this->fileSystem->getDirectoryWrite($this->directoryList::VAR_DIR);
        $fileHash =  hash( 'sha1', $content);
        $filePath = self::CACHE_PATH . DIRECTORY_SEPARATOR. $fileHash;
        try {
            $content = gzcompress($content, 9);
            $cachePath->writeFile($filePath, $content);
        } catch (\Exception $exception) {
            return null;
        }
        return $fileHash;
    }

    /**
     * @param $fileHash
     * @return string|null
     */
    public function getContentFromFile($fileHash)
    {
        $cachePath = $this->fileSystem->getDirectoryRead($this->directoryList::VAR_DIR);
        $filePath = self::CACHE_PATH . DIRECTORY_SEPARATOR. $fileHash;

        try {
            $content = $cachePath->readFile($filePath);
            $content = gzuncompress($content);
        } catch (\Exception $ex) {
            return '';
        }

        return $content;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function clearSavedHashes()
    {
        $ga4CachePath = $this->directoryList->getPath($this->directoryList::VAR_DIR) . DIRECTORY_SEPARATOR . self::CACHE_PATH;

        try {
            if ($this->file->isDirectory($ga4CachePath)) {

                $this->file->createDirectory($ga4CachePath . '.archived' );

                $this->file->rename($ga4CachePath, $ga4CachePath . '.archived' . DIRECTORY_SEPARATOR . time());
            }
        } catch (\Exception $ex) {
            return false;
        }

        return true;
    }


}
