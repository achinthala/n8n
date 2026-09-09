<?php
namespace Dcw\IncstoreShipping\Observer;

use Magento\Framework\Event\ObserverInterface;

class SetItemCustomAttribute implements ObserverInterface
{
    
	protected $_request;

	public function __construct(
		\Magento\Framework\App\RequestInterface $request,
	) {
		$this->_request = $request;
	}
	
	/**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quoteItem = $observer->getQuoteItem();
        $product = $observer->getProduct();
		
				$params = $this->_request->getParams();
				$pdplinedata = [];
				$pdplinedata['PricePerSqft']= $params['pricePerSqft'] ?? 0;
                $pdplinedata['LineItemUnitQuantity']= $params['qty'] ?? 0;
                $pdplinedata['UnitPrice']= $params['custom_price'] ?? 0;
                $pdplinedata['LineItemPrice']= ($pdplinedata['UnitPrice']*$pdplinedata['LineItemUnitQuantity']) ?? 0;
                $pdplinedata['TileSize']= $params['tileSize'] ?? ' ';
                $pdplinedata['Covers']= $params['covers'] ?? 0;
                $pdplinedata['SquareFootage']= $params['squareFootage'] ?? 0;
				$pdplinedata['Length']= $params['length'] ?? 0;
				$pdplinedata['Width']= $params['width'] ?? 0;
				$pdplinedata['PricePerLinearFoot']= $params['PricePerLinearFoot'] ?? 0;
				$pdplinedata['RoomWidth']= $params['RoomWidth'] ?? 0;
				$pdplinedata['RoomLength']= $params['RoomLength'] ?? 0;
				$pdplinedata['RollType']= $params['RollType'] ?? ' ';
				$pdplinedata['QuantityRange']= $params['QuantityRange'] ?? 0;
				$pdplinedata['RollSize']= $params['RollSize'] ?? ' ';
				$pdplinedata['TrailerRollType']= $params['TrailerRollType'] ?? ' ';
				$pdplinedata['CustomLength']= $params['CustomLength'] ?? 0;
				$pdplinedata['CBCShopBy']= $params['CBCShopBy'] ?? ' ';
				$pdplinedata['CBCTileSize']= $params['CBCTileSize'] ?? ' ';
				$pdplinedata['BorderTileQty']= $params['border_tiles_qty'] ?? 0;
				$pdplinedata['CenterTileQty ']= $params['center_tiles_qty'] ?? 0;
				$pdplinedata['CornerTileQty']= $params['corner_tiles_qty'] ?? 0;

				if(!empty($pdplinedata)){
					$pdplinedata_json = json_encode($pdplinedata);		
		
					$quoteItem->setPdpLineItem($pdplinedata_json);
				}
    }
}