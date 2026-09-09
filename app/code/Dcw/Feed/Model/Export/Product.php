<?php

declare(strict_types=1);

namespace Dcw\Feed\Model\Export;

use Amasty\Feed\Model\Export\Product as AmastyProduct;
use Magento\Store\Model\Store;
use Magento\Framework\DB\Select;

class Product extends AmastyProduct
{
    /**
     * Reimplemented from Amasty\Feed\Model\Export\Product to also fetch custom_image_link
     * for each media gallery row so it can be used in the feed (e.g. for Canto images).
     *
     * @param int[] $productIds
     * @return array
     */
    public function getMediaGallery(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        $productEntityJoinField = $this->getProductEntityLinkField();

        $select = $this->_connection->select()->from(
            ['mgvte' => $this->_resourceModel->getTableName('catalog_product_entity_media_gallery_value_to_entity')],
            [
                "mgvte.$productEntityJoinField",
                'mgvte.value_id'
            ]
        )->joinLeft(
            ['mg' => $this->_resourceModel->getTableName('catalog_product_entity_media_gallery')],
            '(mg.value_id = mgvte.value_id)',
            [
                'mg.attribute_id',
                'filename' => 'mg.value',
                // Custom per-image Canto URL stored directly on gallery table
                'custom_image_link' => 'mg.custom_image_link',
            ]
        )->joinLeft(
            ['mgv' => $this->_resourceModel->getTableName('catalog_product_entity_media_gallery_value')],
            "(mg.value_id = mgv.value_id)"
            . "and (mgvte.$productEntityJoinField = mgv.$productEntityJoinField)"
            . 'and mgv.disabled = 0',
            [
                'mgv.label',
                'mgv.position',
                'mgv.disabled',
                'mgv.store_id',
            ]
        )->joinLeft(
            ['ent' => $this->_resourceModel->getTableName('catalog_product_entity')],
            "(mgvte.$productEntityJoinField = ent.$productEntityJoinField)",
            [
                'ent.entity_id'
            ]
        )->where(
            'ent.entity_id IN (?)',
            $productIds
        )->where(
            'mgv.store_id IN (?)',
            [Store::DEFAULT_STORE_ID, $this->_storeId]
        )->order('mgv.position ASC');

        $rowMediaGallery = [];
        $stmt = $this->_connection->query($select);

        while ($mediaRow = $stmt->fetch()) {
            $rowMediaGallery[$mediaRow[$productEntityJoinField]][] = [
                '_media_attribute_id' => $mediaRow['attribute_id'],
                '_media_image' => $mediaRow['filename'],
                '_media_label' => $mediaRow['label'],
                '_media_position' => $mediaRow['position'],
                '_media_is_disabled' => $mediaRow['disabled'],
                '_media_store_id' => $mediaRow['store_id'],
                // custom_image_link can be populated later if you add such a column/table
                'custom_image_link' => $mediaRow['custom_image_link'] ?? null,
            ];
        }

        return $rowMediaGallery;
    }

    /**
     * Get configurable parent entity ID for a given simple product entity ID.
     *
     * @param int $childEntityId
     * @return int|null
     */
    public function getParentIdByChildId(int $childEntityId): ?int
    {
        $linkTable = $this->_resourceModel->getTableName('catalog_product_super_link');
        $select = $this->_connection->select()
            ->from($linkTable, ['parent_id'])
            ->where('product_id = ?', $childEntityId)
            ->limit(1);

        $parentId = $this->_connection->fetchOne($select);

        return $parentId ? (int)$parentId : null;
    }
}


