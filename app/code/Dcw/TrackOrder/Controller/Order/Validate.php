<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 * Validates if order exists for guest order lookup form.
 */

declare(strict_types=1);

namespace Dcw\TrackOrder\Controller\Order;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Dcw\TrackOrder\Model\OrderValidator;

class Validate extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface
{
    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var OrderValidator
     */
    protected $orderValidator;

    /**
     * @param Context $context
     * @param ResultFactory $resultFactory
     * @param OrderValidator $orderValidator
     */
    public function __construct(
        Context $context,
        ResultFactory $resultFactory,
        OrderValidator $orderValidator
    ) {
        parent::__construct($context);
        $this->resultFactory = $resultFactory;
        $this->orderValidator = $orderValidator;
    }

    /**
     * Validate order existence by increment ID
     *
     * @return Json
     */
    public function execute()
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $orderId = trim((string) ($this->getRequest()->getParam('order_id') ?? ''));

        if ($orderId === '') {
            $result->setData(['exists' => false, 'message' => __('Please enter an Order ID.')]);
            return $result;
        }

        try {
            $exists = $this->orderValidator->isOrderExists($orderId);
            $result->setData([
                'exists' => $exists,
                'message' => $exists ? '' : __('Order ID "%1" does not exist. Please check and try again.', $orderId)
            ]);
        } catch (\Exception $e) {
            $result->setData(['exists' => false, 'message' => __('Order ID "%1" does not exist. Please check and try again.', $orderId)]);
        }

        return $result;
    }
}
