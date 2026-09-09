<?php
namespace Dcw\DesignerTool\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\HTTP\Header $httpHeader,
        \Magento\Framework\App\RequestInterface $request,
        \Dcw\DesignerTool\Helper\Mobile $mobile
    ) {
        parent::__construct($context);
        $this->timezoneInterface = $timezoneInterface;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->httpHeader = $httpHeader;
        $this->request = $request;
        $this->mobile = $mobile;
    }

    public function decimalFeetToFeetInches($decimal_feet)
    {
        $feet = (int)$decimal_feet;
        $fraction = $decimal_feet - $feet;
        $inches = (int)(12.0 * $fraction);
        return ['feet'=>$feet,'inches'=>$inches];
    }
    
    public function getProperFtSizeDimension($size_feet)
    {
        $size_feet = str_replace(['"',"'"], '', $size_feet);
        $size_feet = str_replace('�', 'x', $size_feet);
        $size_feet = preg_replace('/\s+/', '', $size_feet);
        
        return $size_feet;
    }

    public function getRatio($num1, $num2)
    {
        $num1 = ceil($num1);
        $num2 = ceil($num2);
        for ($i = $num2; $i > 1; $i--) {
            if (($num1 % $i) == 0 && ($num2 % $i) == 0) {
                $num1 = $num1 / $i;
                $num2 = $num2 / $i;
            }
        }
        return "$num1:$num2";
    }

    public function detectMobile()
    {
        $userAgent = $this->httpHeader->getHttpUserAgent();
        $server = $this->request->getServer();
        $isMobileDevice = $this->mobile->match($userAgent, $server);
        return $isMobileDevice;
    }
}
