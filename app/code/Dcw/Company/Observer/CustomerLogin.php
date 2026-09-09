<?php

namespace Dcw\Company\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerLogin implements ObserverInterface
{
    public function __construct(
        \Dcw\Company\Helper\Data $helper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
    ) {
        $this->helper = $helper;
        $this->messageManager = $messageManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
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
    }
}
