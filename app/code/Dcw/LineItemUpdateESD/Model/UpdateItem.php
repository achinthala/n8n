<?php

namespace Dcw\LineItemUpdateESD\Model;

use Dcw\LineItemUpdateESD\Api\UpdateItemInterface;
use Dcw\LineItemUpdateESD\Api\Data\UpdateItemResponseInterfaceFactory;
use Magento\Sales\Model\Order\ItemRepository;
use Magento\Framework\Exception\LocalizedException;
use Dcw\LineItemUpdateESD\Service\KlaviyoNotificationOrderItemsESD;

/**
 * Class UpdateItem
 *
 * Updates shipping estimate inside pdp_line_item JSON
 * for parent/child order items
 */
class UpdateItem implements UpdateItemInterface
{
    /**
     * @var ItemRepository
     */
    protected $itemRepository;

    /**
     * @var UpdateItemResponseInterfaceFactory
     */
    protected $responseFactory;
    
    /**
     * @var KlaviyoNotificationOrderItemsESD
     */
    protected $klaviyoNotificationOrderItemsESD;
    
    /**
     * UpdateItem constructor.
     *
     * @param ItemRepository $itemRepository
     * @param UpdateItemResponseInterfaceFactory $responseFactory
     * @param KlaviyoNotificationOrderItemsESD $klaviyoNotificationOrderItemsESD
     */
    public function __construct(
        ItemRepository $itemRepository,
        UpdateItemResponseInterfaceFactory $responseFactory,
        KlaviyoNotificationOrderItemsESD $klaviyoNotificationOrderItemsESD
    ) {
        $this->itemRepository  = $itemRepository;
        $this->responseFactory = $responseFactory;
        $this->klaviyoNotificationOrderItemsESD = $klaviyoNotificationOrderItemsESD;
    }

    /**
     * Update shipping estimate for parent/child items
     *
     * @param int $itemId
     * @param string $estimatedShipDate
     * @return \Dcw\LineItemUpdateESD\Api\Data\UpdateItemResponseInterface
     */
    public function updateItem(int $itemId, string $estimatedShipDate)
    {
        $response = $this->responseFactory->create();
        
        if (empty($estimatedShipDate)) {
            $response->setStatus(true);
            $response->setMessage(__('Please provide valid Shipping estimate date.'));
            $response->setItemId($itemId);
            $response->setEstimatedShipDate($estimatedShipDate);
        } else {

            try {
                /** Load requested item */
                $item = $this->itemRepository->get($itemId);

                if (!$item->getId()) {
                    throw new LocalizedException(__('Order item not found.'));
                }

                $itemsToUpdate = [];
                $itemsToUpdate[] = $item;

                /**
                 * CASE 1:
                 * If parent item ID is passed → update child items
                 */
                if (!$item->getParentItemId()) {
                    foreach ($item->getChildrenItems() as $childItem) {
                        $itemsToUpdate[] = $childItem;
                    }
                }

                /**
                 * CASE 2:
                 * If child item ID is passed → update parent item
                 */
                if ($item->getParentItemId()) {
                    $parentItem = $this->itemRepository->get($item->getParentItemId());
                    if ($parentItem->getId()) {
                        $itemsToUpdate[] = $parentItem;
                    }
                }

                /** Update all collected items */
                foreach ($itemsToUpdate as $updateItem) {
                    $this->updatePdpLineItem($updateItem, $estimatedShipDate);
                }

                $response->setStatus(true);
                $response->setMessage(__('Shipping estimate updated successfully.'));
                $response->setItemId($itemId);
                $response->setEstimatedShipDate($estimatedShipDate);

            } catch (\Exception $e) {
                $response->setStatus(false);
                $response->setMessage($e->getMessage());
            }
        }

        return $response;
    }

    /**
     * Update pdp_line_item JSON
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @param string $estimatedShipDate
     * @throws LocalizedException
     */
    private function updatePdpLineItem($item, string $estimatedShipDate)
    {
        $pdpLineItem = $item->getData('pdp_line_item');

        if (empty($pdpLineItem)) {
            return; // skip silently if empty
        }

        $pdpData = json_decode($pdpLineItem, true);

        if (!is_array($pdpData)) {
            throw new LocalizedException(__('Invalid pdp_line_item JSON.'));
        }

        $pdpData['shipping_estimate'] = $estimatedShipDate;

        $item->setData('pdp_line_item', json_encode($pdpData));
        $this->itemRepository->save($item);
        if (!$item->getParentItemId()) {
            $this->klaviyoNotificationOrderItemsESD
                ->triggerOrderItemsESDNotificationToKlaviyo(
                    $item,
                    $estimatedShipDate
                );
        }
    }
}
