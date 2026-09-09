<?php
namespace Dcw\IncstoreShipping\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\Client\Curl;
use Dcw\IncstoreShipping\ViewModel\Data as IncstoreShippingViewModelData;

class OrderPlacedAfterObserver implements ObserverInterface
{
	protected $_orderItems;
	protected $_curl;
	protected $scopeConfig;
    protected $incstoreShippingViewModelData;
    protected $checkoutSession;
   
    public function __construct(
		\Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItems,
		\Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository,
		\Magento\Framework\App\ResourceConnection $resource,
		\Magento\Sales\Model\ResourceModel\Order\Address\CollectionFactory $addressCollection,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		Curl $curl,
        IncstoreShippingViewModelData $incstoreShippingViewModelData,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
		$this->_orderItems = $orderItems;
		$this->_stockItemRepository = $stockItemRepository;
		$this->resource = $resource;
		$this->connection = $resource->getConnection();
		$this->addressCollection = $addressCollection;
		$this->_curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->incstoreShippingViewModelData = $incstoreShippingViewModelData;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
	    $order = $observer->getEvent()->getOrder();
	    $orderId = $order->getId();
		$shipaddress = $this->getShippingAddress($order);
		$postcode = "";
        $street = "";
        $city = "";
        $region = "";
		if(!empty($shipaddress)){
			$postcode = $shipaddress->getPostcode();
            $street = $shipaddress->getStreet();
            $city = $shipaddress->getCity();
            $region = $shipaddress->getRegionCode();
		}
		//$apidata = $this->getItemApi($postcode, $street, $city, $region, $order);
        $apidata = $this->checkoutSession->getShippingApiDataRaw();

        $this->createLog("Checkout Success Action Quote Id: ".$order->getQuoteId());
        $this->createLog("Checkout Success Action Order Id: ".$orderId);
        $this->createLog("Checkout Success Action Order Increment Id: ".$order->getIncrementId());
        $this->createLog("Checkout Success Action Shipping Amount: ".$order->getShippingAmount());
        $this->createLog("Checkout Success Action Session Shipping API Data: ".json_encode($apidata));
       
		if (!empty($apidata) && count($apidata) > 0) {
            if (isset($apidata['data']['lineItemShipments']) && is_array($apidata['data']['lineItemShipments'])) {
                foreach ($apidata['data']['lineItemShipments'] as $key => $data) {

                    //:::::::starting of order item level data store::::::::
                    if(!empty($data)){
                        $shipping_metadata = [];
                        $shipping_metadata['transactionId']= $order->getIncrementId();
                        $shipping_metadata['isValid']= $data['isValid'];
                        $shipping_metadata['message']= $data['message'];
                        $shipping_metadata['lineItemId']= $data['lineItemId'];
                        $shipping_metadata['cost']= $data['cost'];
                        $shipping_metadata['charge']= $data['charge'];
                        $shipping_metadata['name']= $data['fulfillmentLocation']['name'];
                        $shipping_metadata['street']= $data['fulfillmentLocation']['address']['street'];
                        $shipping_metadata['street2']= $data['fulfillmentLocation']['address']['street2'];
                        $shipping_metadata['city']= $data['fulfillmentLocation']['address']['city'];
                        $shipping_metadata['state']= $data['fulfillmentLocation']['address']['state'];
                        $shipping_metadata['zipCode']= $data['fulfillmentLocation']['address']['zipCode'];
                        $shipping_metadata['country']= $data['fulfillmentLocation']['address']['country'];
                        $shipping_metadata['carrierName']= $data['carrierName'];
                        $shipping_metadata_json = json_encode($shipping_metadata);
                        $itemid = $data['lineItemId'];
                        $this->updateItemShippingMetadata($shipping_metadata_json,$itemid,$order);
                    
                        $charge = $data['charge'];
                    
                            
                            if ($charge != "") {
                                // Load the order item
                                $orderItemObj = $this->_orderItems->create()->addFieldToFilter('quote_item_id', $itemid)->getFirstItem();
                            
                                if ($orderItemObj->getId()) { // Ensure the order item exists
                                    $orderId = $orderItemObj->getOrderId(); // Fetch associated order_id
                            
                                    if ($orderId) {
                                    
                                        // Proceed to set shipping charge
                                        $orderItemObj->setIncstoreItemShipping($charge);
                                        $orderItemObj->save();
                                    } else {
                                    
                                    }
                                } else {
                                
                                }
                            }
                    }
                    //:::::::End of order item level data store:::::::::::::
                }
            }
		}
        
    }  
//:::::::starting of order item level data store v2::::::::

    public function updateItemShippingMetadata($data,$itemid,$order){
        foreach ($order->getAllItems() as $item) {
            $item_level_id = $item->getId();
            if($item_level_id==$itemid){
                $item->setShippingMetadata($data);
                $item->save();
            }
        }
    }
//:::::::End of order item level data store v2:::::::::::::

	public function getItemApi($postcode, $street, $city, $state, $order)
    {
		//$params = ['shipToZipCode' => $postcode, 'DeliveryOptions' => new \ArrayObject()];

        if(!empty($street)){
            $street = implode(', ',$street);
        }
        $params['transactionId']= $order->getIncrementId();
        $params['shipToAddress']= ['street' => $street, 'city'=> $city, 'state'=> $state, 'zipCode' => $postcode];
		$params['DeliveryOptions']= ['DeliveryNotification' => true, 'IsLiftGateRequired' => true, 'IsResidential' => true];
        $items = $order->getAllVisibleItems();
        foreach ($items as $item) {
			$itemid = $item->getItemId();
			$orderItemObj = $this->_orderItems->create()->addFieldToFilter('item_id', $itemid)->getFirstItem();
			
            if($item->getWeight()) {
                $unitWeight = $item->getWeight();
                
                $unitWeightCal = $this->incstoreShippingViewModelData->calculateUnitWeight($item);

                if ($unitWeightCal) {
                    $unitWeight = $unitWeightCal;
                }

				$params['lineItems'][] = ['Id' => $item->getId(), 'Sku' => $item->getSku(), 'UnitWeight' => $unitWeight, 'Quantity' => intval($item->getQtyOrdered())];
            }
        }
        $apiurl = $this->scopeConfig->getValue('dcw_shipping_api/incstoreshipping_config/api_url');
        $apikey = $this->scopeConfig->getValue('dcw_shipping_api/incstoreshipping_config/api_key');
        $apiResponse = $this->apiCaller($apiurl, json_encode($params), $apikey);
        if (count($apiResponse) > 0) {
            return $apiResponse;
        }
        return false;
    }
	
	public function getApiPrice($apiResponse, $itemId){
		
		if (count($apiResponse) > 0) {
            foreach ($apiResponse as $res) {
				$charge = 0;
				if($res['lineItem']['id'] == $itemId){
					return $res['charge'];
				} else {
					return $charge;
				}
            }
            return $charge;
        }
        return false;
	}
	
    /* get Shipping address data of specific order */
    public function getShippingAddress($order) {
       //  $order = $this->getOrderData($orderId);
        /* check order is not virtual */
        if(!$order->getIsVirtual()) {
            $orderShippingId = $order->getShippingAddressId();
            $address = $this->addressCollection->create()->addFieldToFilter('entity_id',array($orderShippingId))->getFirstItem();
            return $address;
        }
        return null;
    }
	
	public function apiCaller($url, $params, $apikey)
    {
        $_writer = new \Zend_Log_Writer_Stream(BP .'/var/log/AfterOrder.log');
        $_logger = new \Zend_Log();
        $_logger->addWriter($_writer);
        $headers = ["Content-Type" => "application/json", "x-api-key" => $apikey];
        $this->_curl->setHeaders($headers);
        $this->_curl->post($url, $params);
        $_logger->info('Request : '. $params);

        $response_json = $this->_curl->getBody();
        $_logger->info('Response : '. $response_json);

        $response = (array)json_decode($response_json, true);
        
        if (array_key_exists('errors', $response)) {
            // see errors in shipping.log file
            $_logger->info(json_encode($response['errors']));
            return [];
        }
        return $response;
    }

    public function createLog($msg)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/shippingAPI.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($msg);
    }
}
