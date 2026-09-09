<?php

namespace WeltPixel\GA4\Cron;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Class CleanArchivedCache
 */
class CleanArchivedCache
{
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;

    /**
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     */
    public function __construct(
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file
    ) {
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    public function execute()
    {
        $archivedCachePath = $this->directoryList->getPath($this->directoryList::VAR_DIR)
            . DIRECTORY_SEPARATOR . \WeltPixel\GA4\Model\ServerSide\JsonBuilder::CACHE_PATH  . '.archived';

        try {
            if ($this->file->isDirectory($archivedCachePath)) {
                $this->file->deleteDirectory($archivedCachePath);
            }
        } catch (\Exception $ex) {
            return '';
        }
    }
}
