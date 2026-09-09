<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dcw\Company\Controller\Profile;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Company\Controller\AbstractAction;
use Magento\Framework\Controller\ResultFactory;
use Magento\Customer\Model\Url;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NotFoundException;

/**
 * Class Index
 */
class Index extends \Magento\Company\Controller\Profile\Index
{
    public function __construct(
        \Dcw\Company\Helper\Data $helper,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Company\Model\CompanyContext $companyContext,
        \Psr\Log\LoggerInterface $logger,
        ?Url $customerUrl = null
    ) {
        $this->helper = $helper;
        $this->customer = $customer;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;

        parent::__construct($context, $companyContext, $logger, $customerUrl);
    }
    /**
     * Company profile
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \InvalidArgumentException
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('Company Profile'));
        $customerId=$this->customerSession->getCustomer()->getId();
        $customer = $this->customer->load($customerId);
        $group_id=$customer->getData('group_id');
        $declined_tshirt=(int)$customer->getData('declined_free_tshirt');
        $tshirt_status=$customer->getData('tshirt_status');
        $customerGroup=$this->helper->getGroupName($group_id);
        if ($tshirt_status==0) {
            $tshirt_status_value="Not Availed";
        } else {
            $tshirt_status_value= $this->helper->getTshirtStatusValue($tshirt_status);
        }

        if ($customerGroup=="Pro Tier 0" && $declined_tshirt==0 && $tshirt_status_value=="Not Availed") {
            $addedStatus=$this->helper->addConfigurableProductTocart();
            if ($addedStatus==1) {
                $this->messageManager->addSuccess(__("Free T-Shirt is successfully added to cart"));
            }
            
        }

        return $resultPage;
    }
}
