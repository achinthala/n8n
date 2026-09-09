<?php

namespace Dcw\CustomTax\Controller;

abstract class Index extends \Magento\Framework\App\Action\Action
{
    protected $_customtaxFactory;

    protected $_resultRawFactory;

    protected $_cookieManager;

    protected $_cookieMetadataFactory;

    protected $_phpEnvironmentRequest;

    protected $_monolog;

    protected $_stdTimezone;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Dcw\CustomTax\Model\CustomTaxFactory $customtaxFactory,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest,
        \Magento\Framework\Logger\Monolog $monolog,
        \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone
    ) {
        parent::__construct($context);
        $this->_customtaxFactory = $customtaxFactory;
        $this->_resultRawFactory = $resultRawFactory;
        $this->_cookieManager = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
        $this->_phpEnvironmentRequest = $phpEnvironmentRequest;
        $this->_monolog = $monolog;
        $this->_stdTimezone = $stdTimezone;
    }
}
