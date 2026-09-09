<?php
namespace Dcw\Custom\Block\Adminhtml\Customer;

use Magento\Backend\Block\Template;
use Magento\Framework\UrlInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;

class ForceApproveButton extends Template
{
    protected $urlBuilder;
    protected $customerRepository;
    protected $request;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\UrlInterface $urlBuilder,
        CustomerRepositoryInterface $customerRepository,
        RequestInterface $request,
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->customerRepository = $customerRepository;
        $this->request = $request;
        parent::__construct($context, $data);
    }

    /**
     * Get the customer ID from the request, then generate the URL.
     * @return string
     */
    public function getForceApproveUrl()
    {
        // Get the customer ID from the request parameters
        $customerId = $this->request->getParam('id');
        
        // If a valid customer ID is provided, fetch the customer
        if ($customerId) {
            try {
                $customer = $this->customerRepository->getById($customerId);
                return $this->urlBuilder->getUrl('custom_forceapprove/customer/forceapprove', ['customer_id' => $customer->getId()]);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // Customer not found, handle this case
                return '';
            }
        }
        
        return '';
    }
}
