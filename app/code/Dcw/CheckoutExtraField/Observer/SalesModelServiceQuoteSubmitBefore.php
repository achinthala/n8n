<?php
declare(strict_types=1);

namespace Dcw\CheckoutExtraField\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Serialize\SerializerInterface;
use Dcw\FlooringCalculation\Helper\Data as FlooringCalculationHelper;
use Dcw\ShoppingCart\ViewModel\Data as ShoppingCartData;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;
use Exception;

class SalesModelServiceQuoteSubmitBefore implements ObserverInterface
{
    private $serializer;
    protected $flooringCalculationHelper;
    protected $productRepository;
    protected $logger;
    protected $shoppingCartData;

    public function __construct(
        SerializerInterface $serializer,
        FlooringCalculationHelper $flooringCalculationHelper,
        ShoppingCartData $shoppingCartData,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        $this->serializer = $serializer;
        $this->flooringCalculationHelper = $flooringCalculationHelper;
        $this->shoppingCartData = $shoppingCartData;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

	/**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getQuote();
        $order = $observer->getOrder();

        foreach ($quote->getAllItems() as $quoteItem) {
            $quoteItems[$quoteItem->getId()] = $quoteItem;
            $this->updateEsdValues($quoteItem, 'quote');
        }

        $shippingAddressData = $quote->getShippingAddress()->getData();

        foreach ($order->getAllItems() as $orderItem) {
            $this->updateEsdValues($orderItem, 'order');

            $quoteItem = $quote->getItemById($orderItem->getQuoteItemId());
            $additionalOptions = $quoteItem->getOptionByCode('additional_options');
            if ($additionalOptions) {
                $options = $orderItem->getProductOptions();
                $options['additional_options'] = $this->serializer->unserialize($additionalOptions->getValue());
                $orderItem->setProductOptions($options);
            }
        }
		if (isset($shippingAddressData['phone_ext'])) {
            $order->getShippingAddress()->setPhoneExt($shippingAddressData['phone_ext']);
        }

        return $this;
    }

    public function updateEsdValues($lineItem, $type)
    {
        $pdpLineItemData = $lineItem->getPdpLineItem();
        $itemSku = $lineItem->getSku();

        if ($type == 'order') {
            $itemQty = $lineItem->getQtyOrdered();
        } else {
            $itemQty = $lineItem->getQty();
        }

        if (!isset($shipToTextArray['shipping_estimate'][$itemSku])) {
            try {
                $product = $this->productRepository->get($itemSku);
                $shipToText = $this->flooringCalculationHelper->getExpectedShipDate($product);
                $shipToTextArray['shipping_estimate'][$itemSku] = $shipToText;

                $checkForPromiseDate = $this->shoppingCartData->getProductPromiseDateFromProductObject($product, $itemQty);
                $shipToTextArray['promise_date'][$itemSku] = '';

                if ($checkForPromiseDate) {
                    $shipToTextArray['promise_date'][$itemSku] = $checkForPromiseDate;
                }
            } catch (Exception $e) {
                $this->logger->info("Product not found for SKU: $itemSku");
                $shipToTextArray['shipping_estimate'][$itemSku] = '';
                $shipToTextArray['promise_date'][$itemSku] = '';
            }
        }

        $customFieldData = [];

        if ($pdpLineItemData) {
            $customFieldData = json_decode($pdpLineItemData, true);
        }

        $checkForQtyBackorder = $this->shoppingCartData->checkForQtyBackorder($product, $itemQty);

		if(!$checkForQtyBackorder){
			// Update the 'shipping_estimate' value
            $customFieldData['shipping_estimate'] = $shipToTextArray['shipping_estimate'][$itemSku] ?? '';
		} else {
            $customFieldData['shipping_estimate'] = "";
        }

        // Update the 'promise_date' value
        $customFieldData['promise_date'] = $shipToTextArray['promise_date'][$itemSku] ?? '';

        // Re-encode the custom field data and save it back to the quote item
        $lineItem->setData('pdp_line_item', json_encode($customFieldData));
    }
}
