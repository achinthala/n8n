<?php

namespace Dcw\IncstoreShipping\Model\Plugin\Quote;

use Closure;
use Dcw\IncstoreShipping\ViewModel\Data as IncstoreShippingViewModelData;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\App\ResourceConnection;

class QuoteToOrderItem
{
    /**
     * @var IncstoreShippingViewModelData
     */
    protected $incstoreShippingViewModelData;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    public function __construct(
        IncstoreShippingViewModelData $incstoreShippingViewModelData,
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $quoteRepository,
        ResourceConnection $resource
    ) {
        $this->incstoreShippingViewModelData = $incstoreShippingViewModelData;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->resource = $resource;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\ToOrderItem $subject
     * @param callable $proceed
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param array $additional
     * @return \Magento\Sales\Model\Order\Item
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $additional = []
    ) {
        /** @var $orderItem \Magento\Sales\Model\Order\Item */
        $orderItem = $proceed($item, $additional);//result of function 'convert' in class 'Magento\Quote\Model\Quote\Item\ToOrderItem' 
        $orderItem->setPdpLineItem($item->getPdpLineItem());//set your required
        $orderItem->setShippingMetadata($item->getShippingMetadata());
        $orderItem->setIncstoreItemShipping($item->getIncstoreItemShipping());

        //update quote and order items weight
        // Resolve quote: works for frontend (Quote\Item) and admin (AddressItem) - multiple fallbacks for admin context
        $quote = $item->getQuote();
        if (!$quote && $item instanceof \Magento\Quote\Model\Quote\Address\Item) {
            $address = $item->getAddress();
            $quote = $address ? $address->getQuote() : null;
        }
        if (!$quote && method_exists($item, 'getQuoteItem') && $item->getQuoteItem()) {
            $quote = $item->getQuoteItem()->getQuote();
        }
        if (!$quote) {
            $quoteId = $this->checkoutSession->getQuoteId();
            $quote = $quoteId ? $this->quoteRepository->get($quoteId) : null;
        }

        if ($quote) {

            if($item->getWeight()) {
                $unitWeight = $item->getWeight();

                $unitWeightCal = $this->incstoreShippingViewModelData->calculateUnitWeight($item);

                if ($unitWeightCal) {
                    $unitWeight = $unitWeightCal;
                }

                $orderItemRowWeight = $item->getQty() * $unitWeight;

                // AddressItem uses getQuoteItemId(); Quote\Item uses getId()
                $quoteItemId = $item instanceof \Magento\Quote\Model\Quote\Address\Item
                    ? $item->getQuoteItemId()
                    : $item->getId();
                $quoteItemObj = $quote->getItemById($quoteItemId);

                if ($quoteItemObj) {
                    $quoteItemObj->setWeight($unitWeight);
                    $quoteItemObj->setRowWeight($orderItemRowWeight);
                    $quoteItemObj->save();

                    $quoteItemTable = $this->resource->getTableName('quote_item');
                    $connection = $this->resource->getConnection();

                    $quoteItemQuery = $connection->select()->from($quoteItemTable, 'item_id')
                        ->where('parent_item_id = ?', $quoteItemId);
                    $quoteItemChildId = $connection->fetchOne($quoteItemQuery);

                    if(!empty($quoteItemChildId)) {
                        $quoteItemChildObj = $quote->getItemById($quoteItemChildId);
                        $quoteItemChildObj->setWeight($unitWeight);
                        $quoteItemChildObj->setRowWeight($orderItemRowWeight);
                        $quoteItemChildObj->save();
                    }
                }

                $orderItem->setWeight($unitWeight);
                $orderItem->setRowWeight($orderItemRowWeight);
            }
        }
        //end update quote and order items weight

        return $orderItem;// return an object '$orderItem' which will replace result of function 'convert' in class 'Magento\Quote\Model\Quote\Item\ToOrderItem'
    }
}
