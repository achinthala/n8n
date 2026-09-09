<?php

namespace Dcw\LineItemUpdateESD\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\ItemRepository;
use Magento\Framework\Exception\LocalizedException;
use Dcw\LineItemUpdateESD\Service\KlaviyoNotificationOrderItemsESD;

class Update extends Action
{
    const ADMIN_RESOURCE = 'Magento_Sales::sales';

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var ItemRepository
     */
    protected $itemRepository;
    
    /**
     * @var KlaviyoNotificationOrderItemsESD
     */
    protected $klaviyoNotificationOrderItemsESD;

    /**
     * Constructor
     *
     * @param Action\Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param ItemRepository $itemRepository
     * @param KlaviyoNotificationOrderItemsESD $klaviyoNotificationOrderItemsESD
     */
    public function __construct(
        Action\Context $context,
        OrderRepositoryInterface $orderRepository,
        ItemRepository $itemRepository,
        KlaviyoNotificationOrderItemsESD $klaviyoNotificationOrderItemsESD
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->itemRepository  = $itemRepository;
        $this->klaviyoNotificationOrderItemsESD  = $klaviyoNotificationOrderItemsESD;
    }

    /**
     * Update estimated ship date for visible items
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $orderId = (int)$this->getRequest()->getParam('order_id');
        $items   = (array)$this->getRequest()->getParam('items', []);

        if (!$orderId || empty($items)) {
            $this->messageManager->addErrorMessage(__('Invalid request data.'));
            return $this->_redirectReferer();
        }

        try {
            $order = $this->orderRepository->get($orderId);

            foreach ($items as $itemId => $itemData) {
                if (empty($itemData['estimated_ship_date'])) {
                    continue;
                }

                $this->updateItemAndRelations(
                    (int)$itemId,
                    $itemData['estimated_ship_date']
                );
            }

            $this->messageManager->addSuccessMessage(
                __('Estimated Ship Dates updated successfully.')
            );

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->_redirect(
            'dcw_lineitemupdateesd/order/form',
            ['order_id' => $orderId]
        );
    }

    /**
     * Update item and its parent/child relations
     *
     * @param int $itemId
     * @param string $estimatedShipDate
     * @throws LocalizedException
     */
    private function updateItemAndRelations(int $itemId, string $estimatedShipDate)
    {
        $item = $this->itemRepository->get($itemId);

        if (!$item->getId()) {
            return;
        }

        // Always update current item
        $this->updatePdpLineItem($item, $estimatedShipDate);

        /**
         * If parent item → update children
         */
        if (!$item->getParentItemId()) {
            foreach ($item->getChildrenItems() as $childItem) {
                $this->updatePdpLineItem($childItem, $estimatedShipDate);
            }
        }

        /**
         * If child item → update parent
         */
        if ($item->getParentItemId()) {
            $parentItem = $this->itemRepository->get($item->getParentItemId());
            if ($parentItem->getId()) {
                $this->updatePdpLineItem($parentItem, $estimatedShipDate);
            }
        }
    }

    /**
     * Update pdp_line_item JSON
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @param string $estimatedShipDate
     */
    private function updatePdpLineItem($item, string $estimatedShipDate)
    {
        $pdpLineItem = $item->getData('pdp_line_item');

        if (!$pdpLineItem) {
            return;
        }

        $data = json_decode($pdpLineItem, true);

        if (!is_array($data)) {
            return;
        }

        $data['shipping_estimate'] = $estimatedShipDate;

        $item->setData('pdp_line_item', json_encode($data));
        $this->itemRepository->save($item);
        if (!$item->getParentItemId()) {
            $this->klaviyoNotificationOrderItemsESD->triggerOrderItemsESDNotificationToKlaviyo($item, $estimatedShipDate);
        }
    }
}
