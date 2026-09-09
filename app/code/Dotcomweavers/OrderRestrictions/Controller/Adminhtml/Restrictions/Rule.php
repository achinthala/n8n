<?php
/**
 * OrderRestrictions Rule Controller.
 * @category  Dotcomweavers
 * @package   Dotcomweavers_OrderRestrictions
 * @author    Dotcomweavers
 * @copyright Copyright (c) Dotcomweavers
 */

namespace Dotcomweavers\OrderRestrictions\Controller\Adminhtml\Restrictions;

abstract class Rule extends \Magento\Backend\App\Action
{
    /**
     * Core registry class
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date
     */
    protected $dateFilter;

    /**
     * @var \Dotcomweavers\OrderRestrictions\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter
     * @param \Dotcomweavers\OrderRestrictions\Model\RuleFactory $ruleFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        \Dotcomweavers\OrderRestrictions\Model\RuleFactory $ruleFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->fileFactory = $fileFactory;
        $this->dateFilter = $dateFilter;
        $this->ruleFactory = $ruleFactory;
        $this->logger = $logger;
    }

    /**
     * Initiate rule
     *
     * @return void
     */
    protected function _initRule()
    {
        $rule = $this->ruleFactory->create();
        $this->coreRegistry->register(
            'current_rule',
            $rule
        );
        $id = (int)$this->getRequest()->getParam('id');

        if (!$id && $this->getRequest()->getParam('restriction_id')) {
            $id = (int)$this->getRequest()->getParam('restriction_id');
        }

        if ($id) {
            $this->coreRegistry->registry('current_rule')->load($id);
        }
    }

    /**
     * Initiate action
     *
     * @return Rule
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('Dotcomweavers_OrderRestrictions::manager')
            ->_addBreadcrumb(__('Restriction Rules'), __('Restriction Rules'));
        return $this;
    }

    /**
     * Returns result of current user permission check on resource and privilege
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotcomweavers_OrderRestrictions::manager');
    }
}
