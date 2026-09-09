<?php
namespace Dcw\BlogSeo\Block\Post\View;

use Magento\Store\Model\ScopeInterface;
/**
 * Blog post view opengraph
 */
class Opengraph extends \Magefan\Blog\Block\Post\View\Opengraph
{  

   /**
     * Retrieve published time
     *
     * @return string
     */
    public function getPublishedTime()
    {
        return $this->stripTags(
            $this->getPost()->getPublishTime()
        );
    }

    /**
     * Retrieve created time
     *
     * @return string
     */
    public function getCreatedTime()
    {
        return $this->stripTags(
            $this->getPost()->getCreationTime()
        );
    }

  /**
     * Retrieve modified time
     *
     * @return string
     */
    public function getModifiedTime()
    {
        return $this->stripTags(
            $this->getPost()->getUpdateTime()
        );
    }
    
    /**
     * Retrieve Author
     *
     * @return string
     */
    public function getAuthor()
    {
        $authorId = $this->stripTags(
            $this->getPost()->getAuthorId()
        );
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $author = $objectManager->create('Magefan\Blog\Api\AuthorInterface')->load($authorId);
        return $author->getTitle();
    }

}