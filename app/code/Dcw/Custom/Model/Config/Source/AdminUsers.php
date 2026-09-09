<?php
namespace Dcw\Custom\Model\Config\Source;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\User\Model\ResourceModel\User\CollectionFactory;
use Magento\Framework\Option\ArrayInterface;

class AdminUsers implements ArrayInterface
{
    protected $userCollectionFactory;

    public function __construct(CollectionFactory $userCollectionFactory)
    {
        $this->userCollectionFactory = $userCollectionFactory;
    }

    public function toOptionArray()
    {
        $options = [];
        $users = $this->userCollectionFactory->create()->load();

        foreach ($users as $user) {
            $options[] = [
                'value' => $user->getId(),
                'label' => $user->getUsername()
            ];
        }

        return $options;
    }
}
