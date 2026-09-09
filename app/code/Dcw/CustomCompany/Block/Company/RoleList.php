<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dcw\CustomCompany\Block\Company;

use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Company\Ui\DataProvider\Roles\DataProvider as UiRoles;

class RoleList extends \Magento\Framework\View\Element\Template
{
     /**
      * @var Context
      */
    protected $context;
    
    /**
     * @var CustomerSession
     */
    protected $customerSession;
     /**
      * @var UiRoles
      */
    protected $uiroles;
    
    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param UiRoles $uiroles
     * @param array $data [optional]
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        UiRoles $uiroles,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->uiroles = $uiroles;
        parent::__construct($context, $data);
    }

    /**
     * Get Role Data.
     *
     * @return DataObject[]
     */
    public function getroles()
    {
        $rolesdata=$this->uiroles->getData();
        
        return $rolesdata;
    }
}
