<?php

namespace Dcw\ProductImage\Plugin;

use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryExtensionFactory;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryExtensionInterface;

class ProductImageGalleryLoad
{
    /**
     * @var ProductAttributeMediaGalleryEntryExtensionFactory
     */
    private $extensionFactory;
	
	protected $request;
	
	protected $_resource;
	
	protected $gallery;

    /**
     * @param ProductAttributeMediaGalleryEntryExtensionFactory $extensionFactory
     */
    public function __construct(ProductAttributeMediaGalleryEntryExtensionFactory $extensionFactory, \Magento\Framework\Webapi\Rest\Request $request, \Magento\Framework\App\ResourceConnection $resource, \Magento\Catalog\Model\ResourceModel\Product\Gallery $gallery)
    {
        $this->extensionFactory = $extensionFactory;
		$this->request = $request;
		$this->_resource = $resource;
		$this->gallery = $gallery;
    }

    /**
     * Loads product attribute media gallery entry entity extension attributes
     *
     * @param ProductAttributeMediaGalleryEntryInterface $entity
     * @param ProductAttributeMediaGalleryEntryExtensionInterface|null $extension
     * @return ProductAttributeMediaGalleryEntryExtensionInterface
     */
    public function afterGetExtensionAttributes(
        ProductAttributeMediaGalleryEntryInterface $entity,
		ProductAttributeMediaGalleryEntryExtensionInterface $extension) {
        if ($extension === null) {
            $extension = $this->extensionFactory->create();
        }

		$body = $this->request->getBodyParams();					
		$connection = $this->_resource->getConnection();
		$tableName = $connection->getTableName("catalog_product_entity_media_gallery");
		
		if(!empty($body)){
			if(isset($body['product']['media_gallery_entries']) && !empty($body['product']['media_gallery_entries'])){
				$postdata = $body['product']['media_gallery_entries'];
				foreach($postdata as $data){
					$imgextattr = "";

					if(isset($data['id']) && $data['id']!=""){
						$id = $data['id'];
						$imgextattr = $data['extension_attributes']['canto_url'] ?? '';
						$data = ["custom_image_link" => $imgextattr]; // Key_Value Pair
						$where = ['value_id = ?' => (int)$id];
						$connection->update($tableName, $data, $where);
						$extension->setCantoUrl($imgextattr);
					}
					else {
						$imgextattr = $data['extension_attributes']['canto_url'] ?? '';
						$extension->setCantoUrl($imgextattr);
					}
				}
				
			}
			
		} else {
			$imgInformation = $this->gallery->loadDataFromTableByValueId('catalog_product_entity_media_gallery',[$entity->getId()]);
			$extension->setCantoUrl($imgInformation[0]['custom_image_link']);
		}
        return $extension;
    }

}