<?php
/**
 * Copyright © Incstores, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Incstores\ViewShippingRates\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Incstores\ViewShippingRates\Model\CustomerAddressProvider;
use Psr\Log\LoggerInterface;

class GetCustomerAddresses extends Action implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Incstores_ViewShippingRates::shipping_rates';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var CustomerAddressProvider
     */
    private $customerAddressProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CustomerAddressProvider $customerAddressProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CustomerAddressProvider $customerAddressProvider,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerAddressProvider = $customerAddressProvider;
        $this->logger = $logger;
    }

    /**
     * Execute action to get customer addresses
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        
        try {
            $customerEmail = $this->getRequest()->getParam('email', '');
            
            $this->logger->info('GetCustomerAddresses - Request received', [
                'email' => $customerEmail
            ]);

            if (empty($customerEmail)) {
                throw new LocalizedException(__('Customer email is required'));
            }

            // Get customer addresses
            $customerAddressData = $this->customerAddressProvider->getCustomerAddressesByEmail($customerEmail);
            
            if (empty($customerAddressData)) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('No customer found with email: %1', $customerEmail)
                ]);
            }

            $this->logger->info('GetCustomerAddresses - Addresses retrieved', [
                'email' => $customerEmail,
                'address_count' => count($customerAddressData['addresses'] ?? [])
            ]);

            return $resultJson->setData([
                'success' => true,
                'data' => $customerAddressData
            ]);
            
        } catch (LocalizedException $e) {
            $this->logger->error('GetCustomerAddresses - LocalizedException: ' . $e->getMessage());
            
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('GetCustomerAddresses - Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while retrieving customer addresses.')
            ]);
        }
    }
}