<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-search-ultimate
 * @version   2.1.4
 * @copyright Copyright (C) 2023 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Dcw\AdvanceSearch\Index\Magefan\Blog\Post;

use Magento\Framework\App\ObjectManager;
use Mirasvit\Search\Index\AbstractInstantProvider;

class InstantProvider extends \Mirasvit\Search\Index\Magefan\Blog\Post\InstantProvider
{
    public function getItems(int $storeId, int $limit, int $page = 1): array
    {
        $items = [];

        foreach ($this->getCollection($limit) as $model) {
            $items[] = $this->mapItem($model, $storeId);
        }

        return $items;
    }

    public function getSize(int $storeId): int
    {
        return $this->getCollection(0)->getSize();
    }

    public function map(array $documentData, int $storeId): array
    {
        foreach ($documentData as $entityId => $itm) {
            $entity = ObjectManager::getInstance()->create(\Magefan\Blog\Model\Post::class)
                ->load($entityId);

            $map = $this->mapItem($entity, $storeId);

            $documentData[$entityId]['_instant'] = $map;
        }

        return $documentData;
    }

    /**
     * @param \Magefan\Blog\Model\Post $model
     */
    private function mapItem($model, int $storeId): array
    {
        if (!empty($model->getContent())) {
            $content = strip_tags($model->getContent());
            $description = substr($content, 0, 100);
        } else {
            $description = $model->getContent();
        }
        
        return [
            'name' => $model->getTitle(),
            'url'  => $model->getCanonicalUrl(),
            'description'  => $description,
        ];
    }
}
