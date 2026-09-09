<?php
/**
 * Copyright © Incstores. All rights reserved.
 */
declare(strict_types=1);

namespace Incstores\QuoteExtensions\Plugin\LoginAsCustomer;

use Magento\LoginAsCustomerApi\Api\Data\IsLoginAsCustomerEnabledForCustomerResultInterface;
use Magento\LoginAsCustomerApi\Api\Data\IsLoginAsCustomerEnabledForCustomerResultInterfaceFactory;
use Magento\LoginAsCustomerAssistance\Model\Processor\IsLoginAsCustomerAllowedResolver;

/**
 * Plugin to bypass Remote Assist validation check
 *
 * This plugin bypasses the customer assistance validation that normally
 * blocks login if the customer hasn't enabled "Remote Shopping Assistance".
 * It allows admins to login as any customer regardless of their assistance preference.
 */
class BypassRemoteAssistValidationPlugin
{
    /**
     * @var IsLoginAsCustomerEnabledForCustomerResultInterfaceFactory
     */
    private $resultFactory;

    /**
     * @param IsLoginAsCustomerEnabledForCustomerResultInterfaceFactory $resultFactory
     */
    public function __construct(
        IsLoginAsCustomerEnabledForCustomerResultInterfaceFactory $resultFactory
    ) {
        $this->resultFactory = $resultFactory;
    }

    /**
     * Bypass the assistance validation check
     *
     * Instead of checking if the customer has enabled remote assistance,
     * always return an empty messages array (indicating validation passed).
     *
     * @param IsLoginAsCustomerAllowedResolver $subject
     * @param callable $proceed
     * @param int $customerId
     * @return IsLoginAsCustomerEnabledForCustomerResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        IsLoginAsCustomerAllowedResolver $subject,
        callable $proceed,
        int $customerId
    ): IsLoginAsCustomerEnabledForCustomerResultInterface {
        // Return empty messages array, indicating the customer is allowed
        return $this->resultFactory->create(['messages' => []]);
    }
}
