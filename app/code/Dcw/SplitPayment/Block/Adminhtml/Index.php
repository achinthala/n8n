<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dcw\SplitPayment\Block\Adminhtml;

use Magento\Eav\Model\AttributeDataFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\Address;

/**
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Index extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /**
     * Customer service
     *
     * @var \Magento\Customer\Api\CustomerMetadataInterface
     */
    protected $metadata;

    /**
     * Group service
     *
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * Metadata element factory
     *
     * @var \Magento\Customer\Model\Metadata\ElementFactory
     */
    protected $_metadataElementFactory;

    /**
     * @var Address\Renderer
     */
    protected $addressRenderer;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Customer\Api\CustomerMetadataInterface $metadata,
        \Magento\Customer\Model\Metadata\ElementFactory $elementFactory,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Payment\Model\Config $paymentConfig,
        \ParadoxLabs\Authnetcim\Helper\Data $anetHelper,
        array $data = []
    ) {
        $this->groupRepository = $groupRepository;
        $this->metadata = $metadata;
        $this->_metadataElementFactory = $elementFactory;
        $this->addressRenderer = $addressRenderer;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->_paymentConfig = $paymentConfig;
        $this->anetHelper = $anetHelper;
        parent::__construct($context, $registry, $adminHelper, $data);
    }

    public function getCcDetails()
    {
        $split_payment_transaction_table = $this->connection->getTableName("split_payment_transaction");
        $select = $this->connection->select()->from($split_payment_transaction_table, ['*'])->where('order_id = ?', $this->getOrder()->getId());
        return $this->connection->fetchAll($select);
    }

    public function getCcTypeName($ccType)
    {
        $types = $this->_paymentConfig->getCcTypes();
        if (isset($types[$ccType])) {
            return $types[$ccType];
        }
        return empty($ccType) ? __('N/A') : $ccType;
    }

    public function anetHelper()
    {
        return $this->anetHelper;
    }
}