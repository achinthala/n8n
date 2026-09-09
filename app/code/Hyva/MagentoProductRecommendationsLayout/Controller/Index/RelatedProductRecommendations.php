<?php
namespace Hyva\MagentoProductRecommendationsLayout\Controller\Index;

use \Magento\Framework\Controller\ResultFactory;

class RelatedProductRecommendations extends \Magento\Framework\App\Action\Action
{
    protected $formKey;
    protected $productFactory;
    protected $productCollectionFactory;
    protected $resultJsonFactory;
    protected $json;
    protected $viewModels;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Hyva\Theme\Model\ViewModelRegistry $viewModels,
    ) {
        $this->formKey = $formKey;
        $this->productFactory = $productFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->json = $json;
        $this->viewModels = $viewModels;
        parent::__construct($context);
    }
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $product_ids = $this->getRequest()->getParam('product_ids');
            if (!empty($product_ids)) {
                $product_id_arr = explode(',',$product_ids);
            }
            //$product_id_arr = $this->json->unserialize($product_ids);
            //print_r($product_id_arr);

            $product_recomndatns = $this->productCollectionFactory->create();
            $product_recomndatns->addAttributeToSelect('*');
            $product_recomndatns->addFieldToFilter('entity_id', array('in' => $product_ids));
            $product_recomndatns->addAttributeToFilter('status', 1);
            $product_recomndatns->addAttributeToFilter('visibility', 4);

            if (count($product_recomndatns) > 0) {
                $modalViewModel = $this->viewModels->require(\Hyva\Theme\ViewModel\Modal::class);
                $sliderViewModel = $this->viewModels->require(\Hyva\Theme\ViewModel\Slider::class);
                $itemTemplate = 'Magento_Catalog::product/list/item_product_recommendations.phtml';
                $containerTemplate ='Magento_Catalog::product/slider/product-slider-container.phtml';
                $sliderHtml = $sliderViewModel->getSliderForItems($itemTemplate, $product_recomndatns, $containerTemplate)->toHtml();
                $response = $resultJson->setData(['success' => 1, 'sliderHtml' => $sliderHtml]);
            } else {
                $response = $resultJson->setData(['success' => 0,'message'=>'There is no Recommendation Products']);
            }

        } catch (\Exception $e) {
            $response = $resultJson->setData(['success' => 0,'message'=>$e->getMessage()]);
        }

        return $response;
    }
}
