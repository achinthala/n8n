<?php

namespace Dcw\ProductImage\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ProductRepository;

class Productsaveafter implements ObserverInterface
{
    const BASE_IMAGE_SET_FLAG = 'base_image_set';

	protected $_resource;
	protected $request;
	protected $productRepository;
	 
	public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request,
		\Magento\Framework\App\ResourceConnection $resource,
		ProductRepository $productRepository
    ) {
        $this->request = $request;
		$this->_resource = $resource;
		$this->productRepository = $productRepository;
    }
	
	public function execute (\Magento\Framework\Event\Observer $observer)
	{ 

		$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/api_canto_url.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
    
		$body = $this->request->getBodyParams();
		$connection = $this->_resource->getConnection();
		$tableName = $connection->getTableName("catalog_product_entity_media_gallery");
		$product = $observer->getProduct();

		$product_id = $product->getId();
		$row_id =  $product->getData('row_id');

		// Check if the base image has already been set to prevent recursion
        if ($product->getData(self::BASE_IMAGE_SET_FLAG)) {
            return;
        }

		//======== For Product API   START ===================
		if(isset($body['product']['media_gallery_entries']) && !empty($body['product']['media_gallery_entries'])){
			//$image = $product->getData('image');

			$logger->info('body = '.print_r($body,true));
			$logger->info('product_id = '.$product_id .'  |   row_id = '.$row_id); 

			$c_p_e_m_g_v_table = $connection->getTableName("catalog_product_entity_media_gallery_value");
			$select_media_id = $connection->select()
			->from($c_p_e_m_g_v_table, 'value_id')
			->order('value_id ASC')
			->where('row_id = ?', $row_id);
			$media_value_id_arr = $connection->fetchAll($select_media_id);

			$logger->info('media_value_id_arr = '.print_r($media_value_id_arr,true));

			$postdata = $body['product']['media_gallery_entries'];
			foreach($postdata as $key => $data){
				if (isset($data['extension_attributes']) && !empty($data['extension_attributes']['canto_url']) && $media_value_id_arr[$key]['value_id']) {
					$logger->info('media_value_id = '.print_r($media_value_id_arr[$key]['value_id'],true)); 

					$imgextattr = $data['extension_attributes']['canto_url'];
					$data = ["custom_image_link" => $imgextattr]; // Key_Value Pair
					$where = ['value_id = ?' => $media_value_id_arr[$key]['value_id']];
					$connection->update($tableName, $data, $where);
				}
			}

			$logger->info('============================================'); 
		}
		//======== For Product API   END ===================

		//======== For Media API   START ===================
		if(isset($body['entry']) && !empty($body['entry'])){

			$logger->info('body = '.print_r($body,true));
			$logger->info('product_id = '.$product_id .'  |   row_id = '.$row_id); 

			$c_p_e_m_g_v_table = $connection->getTableName("catalog_product_entity_media_gallery_value");
			$select_media_id = $connection->select()
			->from($c_p_e_m_g_v_table, 'value_id')
			->order('value_id DESC')
			->where('row_id = ?', $row_id)
			->limit(1);
			$media_value_id = $connection->fetchOne($select_media_id);

			$logger->info('SQL media_value_id = '.$select_media_id); 

			$logger->info('media_value_id A = '.$media_value_id); 

			$postdata = $body['entry'];
			if(isset($data['extension_attributes']) && !empty($postdata['extension_attributes']['canto_url']) && !empty($media_value_id)) {  //if(isset($image) && $image!=""){

				$logger->info('media_value_id B = '.$media_value_id); 

				$imgextattr = $postdata['extension_attributes']['canto_url'];
				$data = ["custom_image_link" => $imgextattr]; // Key_Value Pair
				$where = ['value_id = ?' => $media_value_id];
				$connection->update($tableName, $data, $where);

			}

			$logger->info('============================================'); 
		}
		//======== For Media API   END ===================

		// Reload the product to ensure all data is fully loaded
		$productId = $product->getId();
        $product = $this->productRepository->getById($productId);
		$productRowId = $product->getRowId();
		
		//update the first image as base, small, thumb, swatch
		$galleryValueQuery = "select * from catalog_product_entity_media_gallery_value where row_id=$productRowId order by position asc limit 1";
		$galleryValueQueryResult = $connection->fetchAll($galleryValueQuery);

		// Check if the result is not empty
		if (!empty($galleryValueQueryResult)) {
			// Fetch the value_id from the result
			$valueId = $galleryValueQueryResult[0]['value_id'];

			$galleryValueQueryUpdate = "update catalog_product_entity_varchar set value = (select value from catalog_product_entity_media_gallery where value_id = $valueId)
			where row_id = $productRowId
			and attribute_id IN (
			select attribute_id from eav_attribute where attribute_code IN ('image', 'small_image', 'thumbnail', 'swatch_image') 
			and entity_type_id = (select entity_type_id FROM eav_entity_type where entity_type_code = 'catalog_product')
			)";

			$connection->query($galleryValueQueryUpdate);
		}
    }
}
