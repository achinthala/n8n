<?php
namespace Dcw\Ordersaveadmin\Plugin;

use Magento\Sales\Api\Data\OrderInterface;

class OrderRepositoryPlugin
{
    public function afterGet(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        OrderInterface $order
    ) {
        
        $extensionAttributes = $order->getExtensionAttributes() ?? $this->extensionAttributesFactory->create();
        //$adminUserAssistance = $order->getData("admin_user_assistance");
        //$adminUserAssistance = $order->getData("admin_user_assistance");
        $adminUserAssistance = $order->getAdminUserAssistance();
        if(!$adminUserAssistance){
            $adminUserAssistance='';
        }
        $extensionAttributes->setAdminUserAssistance($adminUserAssistance);
        $order->setExtensionAttributes($extensionAttributes); 
        return $order;
    }
    
    public function afterGetList(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        \Magento\Sales\Api\Data\OrderSearchResultInterface $searchResult
    ) {
        
        foreach ($searchResult->getItems() as $order) {
            $extensionAttributes = $order->getExtensionAttributes() ?? $this->extensionAttributesFactory->create();
            $adminUserAssistance = $order->getAdminUserAssistance();
            if(!$adminUserAssistance){
                $adminUserAssistance='';
            }
            $extensionAttributes->setAdminUserAssistance($adminUserAssistance);
            $order->setExtensionAttributes($extensionAttributes);
        } 
        return $searchResult;
    } 
}
