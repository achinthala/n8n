<?php
namespace Dcw\RequestQuote\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AdminUsernames implements OptionSourceInterface
{
    protected $collectionFactory;
    
    public function __construct(
        \Magento\User\Model\ResourceModel\User\CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }
    public function toOptionArray()
    {
        $userCollection = $this->collectionFactory->create();

        $adminUserNameArr = [];
        foreach ($userCollection as $user) {
            $adminUserNameArr[] = ['value' => $user->getUsername(), 'label' => $user->getUsername()];
        }
        return $adminUserNameArr;
    }
}