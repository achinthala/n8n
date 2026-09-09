<?php

namespace Dcw\Localization\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Customer\Model\SessionFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\Http\Context;
use Magento\Catalog\Model\Product\Attribute\Repository;

class Data extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface scopeConfig
     */

     protected $scopeConfig;

    /**
     * @var RemoteAddress remoteAddress
     */

     protected $remoteAddress;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var CookieMetadataFactory 
     */
    protected $cookieMetadataFactory;

    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;
    
    /**
     * @var SessionFactory
     */
    protected $customerSessionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var Context 
     */

     protected $httpContext;

     /**
     * @var UserContextInterface
     */

     protected $userContext;

     /**
     * @var Repository
     */

     protected $attibuteRepository;

    /**
     *  @param  ScopeConfigInterface $scopeConfig
     *  @param  RemoteAddress $remoteAddress
     *  @param  CookieManagerInterface $cookieManager
     *  @param  CookieMetadataFactory $cookieMetadataFactory
     *  @param  SessionManagerInterface $sessionManager
     *  @param  SessionFactory $customerSessionFactory
     *  @param  CustomerRepositoryInterface $customerRepository
     *  @param  AddressRepositoryInterface $addressRepository
     *  @param  Context $httpContext
     *  @param  UserContextInterface $userContext
     *  @param  Repository $attibuteRepository
     */ 

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RemoteAddress $remoteAddress,
        CookieMetadataFactory $cookieMetadataFactory,
        CookieManagerInterface $cookieManager,
        SessionManagerInterface $sessionManager,
        SessionFactory $customerSessionFactory,
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository,
        Context $httpContext,
        UserContextInterface $userContext,
        Repository $attibuteRepository
    ) {
        $this->scopeConfig=$scopeConfig;
        $this->remoteAddress=$remoteAddress;
        $this->cookieMetadataFactory=$cookieMetadataFactory;
        $this->cookieManager=$cookieManager;
        $this->sessionManager = $sessionManager;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->httpContext = $httpContext;
        $this->userContext = $userContext;
        $this->attibuteRepository = $attibuteRepository;
    }
    public function getCoast() 
    {
        $coast="";
        //$visitorIp = $this->remoteAddress->getRemoteAddress();   
        $forwardedForHeader = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
        if ($forwardedForHeader) {
            $ips = explode(',', $forwardedForHeader);
            $visitorIp = trim($ips[0]);
        } else {
            $visitorIp = $this->remoteAddress->getRemoteAddress();
        }
        
        $isLoggedIn = $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        if ($isLoggedIn) {
            $customerSession = $this->customerSessionFactory->create();
            $customerId = $customerSession->getCustomer()->getId();
            $customer = $this->customerRepository->getById($customerId);
            $shippingAddressId = $customer->getDefaultShipping();
            $shippingAddress =null;
            if($shippingAddressId) {
                $shippingAddress = $this->addressRepository->getById($shippingAddressId);
                $region=$shippingAddress->getRegion()->getRegion();
                $ships_from = $this->cookieManager->getCookie('ships_from'); 
            }
            
            if($shippingAddress) {
                $coast=$this->getCoastData($region);
            } else if (isset($_COOKIE['ships_from'])) {
                $coast = $this->cookieManager->getCookie('ships_from');
            }  else {
                //$details = @unserialize(file_get_contents("http://ip-api.com/php/" . $visitorIp));
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "http://ip-api.com/php/" . $visitorIp);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);

                $details = @unserialize($response); 
                
                if ($details['status']!='fail') {
                    $region= $details['regionName'];
                    $coast=$this->getCoastData($region);
                }            
            }
        }else {         
            $ships_from = $this->cookieManager->getCookie('ships_from');

            if (empty($ships_from)) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "http://ip-api.com/php/" . $visitorIp);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);

                $details = @unserialize($response);

                if ($details !== false && is_array($details)) {
                    if (isset($details['status']) && $details['status'] != 'fail') {
                        $region= $details['regionName'];
                        $coast=$this->getCoastData($region);
                    }
                }     
            } else {
                $coast = $this->cookieManager->getCookie('ships_from');    
            }
        }

        return $coast;
    }

    public function getCoastData($region)
    {
        $coast="";
        $east_coast_value= $this->scopeConfig->getValue(
            'dcw_localization/localization/east_coast',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        );
        $west_coast_value= $this->scopeConfig->getValue(
            'dcw_localization/localization/west_coast',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        );
        $midwest_value= $this->scopeConfig->getValue(
            'dcw_localization/localization/midwest',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        );
        /*$south_coast_value= $this->scopeConfig->getValue(
            'dcw_localization/localization/south_coast',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        );*/
        $east_coast = array_map('trim', explode(',', $east_coast_value));
        $west_coast = array_map('trim', explode(',', $west_coast_value));
        $midwest = array_map('trim', explode(',', $midwest_value));
        //$south_coast = array_map('trim', explode(',', $south_coast_value));
        if (in_array($region,$east_coast)) {
            $coast="East Coast";
        }
        if (in_array($region,$west_coast)) {
            $coast="West Coast";
        }
        if (in_array($region,$midwest)) {
            $coast="Midwest";
        }
        /*if (in_array($region,$south_coast)) {
            $coast="South Coast";
        }*/
        
        return $coast;
    }
    public function getShipFromValue()
    {
        $selectOptions = $this->attibuteRepository->get('ships_from')->getOptions();
        $options=[];
        foreach ($selectOptions as $selectOption) {
            $options[$selectOption->getData('label')]=$selectOption->getData('value');
        }
        
        return $options;

    }
    /** Set Custom Cookie using Magento 2 */
    public function setCustomCookie($coast)
    {
        $publicCookieMetadata = $this->cookieMetadataFactory
        ->createPublicCookieMetadata();
        $publicCookieMetadata->setDurationOneYear()
        ->setPath($this->sessionManager->getCookiePath())
        ->setDomain($this->sessionManager->getCookieDomain());
        $publicCookieMetadata->setHttpOnly(false);
        return $this->cookieManager->setPublicCookie(
            'ships_from_helper',
            $coast,
            $publicCookieMetadata
        );
    }

    /** Get Custom Cookie using */
    public function getCustomCookie()
    {
        return $this->cookieManager->getCookie('ships_from');
    }
}
