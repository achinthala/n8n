<?php
namespace Dcw\Custom\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class ForceApprove extends Action
{
    protected $customerRepository;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context);
        $this->customerRepository = $customerRepository;
    }

    /**
     * Approve the customer account by setting their status as active.
     */
    public function execute()
    {
        // Get the customer ID from the request parameters
        $customerId = $this->getRequest()->getParam('customer_id');

        if ($customerId) {
            try {
                // Load the customer by ID from the repository
                $customer = $this->customerRepository->getById($customerId);
                
                // Use the Customer Repository's save method to activate the account
                // Set the customer as active (approve them)
                $customer->setConfirmation(null);
                $customer->setCustomAttribute('is_active', 1);  // Approve the customer by setting the 'is_active' attribute to 1
                $this->customerRepository->save($customer); // Save the changes

                // Success message
                $this->messageManager->addSuccessMessage(__('Customer account approved.'));
                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setUrl($this->_redirect->getRefererUrl());
            } catch (NoSuchEntityException $e) {
                // Handle customer not found
                $this->messageManager->addErrorMessage(__('Customer not found.'));
                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setUrl($this->_redirect->getRefererUrl());
            }
        } else {
            // Handle missing customer ID
            $this->messageManager->addErrorMessage(__('Invalid customer ID.'));
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setUrl($this->_redirect->getRefererUrl());
        }
    }
}
