<?php

declare(strict_types=1);

namespace Dcw\RequestQuote\Model\Config\Source;

use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory;
use Magento\Authorization\Model\Acl\Role\Group as RoleGroup;
use Magento\Framework\Option\ArrayInterface;

/**
 * Source model for admin roles dropdown
 */
class AdminRoles implements ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    protected $roleCollectionFactory;

    /**
     * @param CollectionFactory $roleCollectionFactory
     */
    public function __construct(
        CollectionFactory $roleCollectionFactory
    ) {
        $this->roleCollectionFactory = $roleCollectionFactory;
    }

    /**
     * Return array of options as value-label pairs
     * Only includes admin roles (user_type = 1) and group roles (role_type = 'G')
     * Excludes integration roles (user_type = 2) and user-specific roles (role_type = 'U')
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        $roles = $this->roleCollectionFactory->create();

        $roles->addFieldToFilter('user_type', 2);
        
        // Filter only group roles (role_type = 'G'), exclude user-specific roles (role_type = 'U')
        // Group roles are shared roles that can be assigned to multiple users
        // User roles are individual roles assigned to specific users
        $roles->addFieldToFilter('role_type', RoleGroup::ROLE_TYPE);
        
        foreach ($roles as $role) {
            $options[] = [
                'value' => $role->getRoleId(),
                'label' => $role->getRoleName()
            ];
        }

        return $options;
    }
}

