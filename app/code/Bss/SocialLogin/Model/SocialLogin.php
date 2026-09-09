<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_AjaxSocialLogin
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\SocialLogin\Model;

use Bss\SocialLogin\Helper\Data;
use Magento\Customer\Model\Customer;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Math\Random;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManager;

/**
 * @SuppressWarnings(PHPMD)
 * @codingStandardsIgnoreFile
 */
class SocialLogin extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var null
     */
    protected $type = null;

    /**
     * @var null
     */
    protected $websiteId = null;

    /**
     * @var null
     */
    protected $redirectUri = null;

    /**
     * @var array
     */
    protected $accountData = [];

    /**
     * @var null
     */
    protected $photoDir = null;

    /**
     * @var null
     */
    protected $applicationId = null;

    /**
     * @var null
     */
    protected $secret = null;

    /**
     * @var string
     */
    protected $response_type = 'code';

    /**
     * @var array
     */
    protected $dob = [];

    /**
     * @var array
     */
    protected $gender = ['male', 'female'];

    /**
     * @var \Bss\SocialLogin\Helper\Data
     */
    protected $helper;

    /**
     * @inheritdoc
     */
    protected $fields;

    /**
     * @inheritdoc
     */
    protected $popupSize;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;

    /**
     * @inheritdoc
     */
    protected $authUrl;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected \Magento\Store\Model\StoreManager $storeManager;

    /**
     * @var \Magento\Framework\Encryption\Encryptor
     */
    protected \Magento\Framework\Encryption\Encryptor $encryptor;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Random
     */
    protected $random;

    /**
     * @var File
     */
    protected $file;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Customer $customer
     * @param Data $helper
     * @param StoreManager $storeManager
     * @param Filesystem $filesystem
     * @param Encryptor $encryptor
     * @param Random $random
     * @param File $file
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Customer $customer,
        \Bss\SocialLogin\Helper\Data $helper,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\Math\Random $random,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->customer = $customer;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
        $this->filesystem = $filesystem;
        $this->random = $random;
        $this->file = $file;
    }

    /**
     * @inheritdoc
     */
    public function _construct()
    {
        $this->_init('Bss\SocialLogin\Model\ResourceModel\SocialLogin');
        $this->websiteId = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Store\Model\StoreManager')->getWebsite()->getId();
        $this->helper = \Magento\Framework\App\ObjectManager::getInstance()->get('Bss\SocialLogin\Helper\Data');
        $this->redirectUri = $this->helper->getCallbackURL($this->type);
        $this->photoDir = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Filesystem')
            ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
            ->getAbsolutePath('sociallogin'. DIRECTORY_SEPARATOR .'photo');
        if ($this->helper->getConfig($this->helper->getConfigSectionId() .'/'. $this->type .'/client_id') != null) {
            $this->applicationId = trim($this->helper->getConfig($this->helper->getConfigSectionId() .'/'. $this->type .'/client_id'));
        }
        $encryptor = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Encryption\Encryptor');
        if ($encryptor->decrypt($this->helper->getConfig($this->helper->getConfigSectionId() .'/'. $this->type .'/secret')) != '') {
            $this->secret = trim($encryptor->decrypt($this->helper->getConfig($this->helper->getConfigSectionId() .'/'. $this->type .'/secret')));
        }
    }

    /**
     * @return bool
     */
    public function enabled()
    {
        return (bool)$this->helper->getConfig($this->helper->getConfigSectionId() .'/'. $this->type .'/enable');
    }

    /**
     * @return string
     */
    public function getResponseType()
    {
        return $this->response_type;
    }

    /**
     * Set account data
     *
     * @param mixed $key
     * @param null $value
     * @return $this
     */
    public function setAccountData($key, $value = null)
    {
        if (is_array($key)) {
            $this->accountData = array_merge($this->accountData, $key);
        } else {
            $this->accountData[$key] = $value;
        }
        return $this;
    }

    /**
     * Get account data
     *
     * @param null $key
     * @return array|mixed|null
     */
    public function getAccountData($key = null)
    {
        if ($key !== null) {
            return $this->accountData[$key] ?? null;
        }
        return $this->accountData;
    }

    /**
     * Filter data
     *
     * @param $data
     * @return array
     */
    protected function _filterData($data)
    {
        $_data = [];
        foreach ($this->fields as $customerField => $userField) {
            $_data[$customerField] = ($userField && isset($data[$userField])) ? $data[$userField] : null;
        }
        $_data['firstname'] = $_data['firstname']?: '...';
        $_data['lastname'] = $_data['lastname']?: '...';
        $_data['email'] = $this->getEmail($_data['email']);
        $_data['dob'] = (!empty($_data['dob'])) ? call_user_func_array([$this, '_getDob'], array_merge([$_data['dob']], $this->dob)) : null;
        if (!empty($_data['gender'])) {
            $options = $this->customer->getAttribute('gender')->getSource()->getAllOptions(false);
            switch ($_data['gender']) {
                case $this->gender[0]: $_data['gender'] = $options[0]['value']; break;
                case $this->gender[1]: $_data['gender'] = $options[1]['value']; break;
                default: $_data['gender'] = 0;
            }
        } else {
            $_data['gender'] = 0;
        }
        $_data['taxvat'] = '0';
        $_data['password'] = $this->_getRandomPassword();
        return $_data;
    }

    /**
     * Get email
     *
     * @param $email
     * @return string
     */
    protected function getEmail($email)
    {
        if (empty($email)) {
            $email = $this->_getRandomEmail();
        }
        return $email;
    }

    /**
     * Get Dob
     *
     * @param string $date
     * @param string $month
     * @param string $day
     * @param string $year
     * @param string $separator
     * @return string
     */
    protected function _getDob($date, $month = 'month', $day = 'day', $year = 'year', $separator = '/')
    {
        $dateDob = explode($separator, $date);

        $result = [
            'year' => '0000',
            'month' => '00',
            'day' => '00'
        ];
        $result[$month] = $dateDob[0];
        if (isset($dateDob[1])) {
            $result[$day] = $dateDob[1];
        }
        if (isset($dateDob[2])) {
            $result[$year] = $dateDob[2];
        }
        return implode('-', array_values($result));
    }

    /**
     * Get random email
     *
     * @return string
     */
    protected function _getRandomEmail()
    {
        $len = 12;
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        return $this->random->getRandomString($len, $chars) .'@'. $this->type.'-user.com';
    }

    /**
     * Get random password
     *
     * @return bool|string
     */
    protected function _getRandomPassword()
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
        return substr(str_shuffle($chars), 0, 8);
    }

    /**
     * Get button
     *
     * @return array
     */
    public function getButton()
    {
        $_url = null;
        $className = 'Bss\SocialLogin\Model\\'. ucfirst($this->type);
        $model = \Magento\Framework\App\ObjectManager::getInstance()->get($className);
        $store = $this->storeManager->getStore();
        if ($this->type == 'twitter') {
            $_url = $store->getUrl('sociallogin/account/login', ['type' => $this->type, 'refresh' => time()]);
        } else {
            $_url = $model->getButtonUrl();
        }

        $media = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) .'sociallogin/';
        $small_icon = $this->helper->getConfig($this->helper->getConfigSectionId() .'/'. $this->type .'/small_icon');
        $image = $small_icon? $media . $small_icon : null;

        return [
            'href' => $_url,
            'type' => $this->type,
            'image' => $image,
            'login_text' => $this->helper->getConfig($this->helper->getConfigSectionId() .'/'. $this->type .'/login_btn_text'),
            'register_text' => $this->helper->getConfig($this->helper->getConfigSectionId() .'/'. $this->type .'/register_btn_text'),
            'popup_width' => $this->popupSize[0],
            'popup_height' => $this->popupSize[1],
        ];
    }

    /**
     * Get button url
     *
     * @return string|null
     */
    public function getButtonUrl()
    {
        if (empty($this->applicationId) || empty($this->secret)) {
            $button_url = null;
        } else {
            $button_url = $this->authUrl;
        }
        return $button_url;
    }

    /**
     * Set customer id by token id
     *
     * @param int $customerId
     * @return $this
     * @throws \Exception
     */
    public function setCustomerIdByTokenId($customerId)
    {
        $data = [
            'type' => $this->type,
            'token_id' => $this->getAccountData('token_id'),
            'customer_id' => $customerId

        ];

        $fakemail = strpos($this->getAccountData('email'), $this->type.'-user.com');
        if ($fakemail) {
            $data['password_fake_email'] = $this->getAccountData('password');
        }
        $this->addData($data)->save();
        return $this;
    }

    /**
     * Get customer id
     *
     * @return int|mixed
     */
    public function getCustomerIdByTokenId()
    {
        $customerId = $this->_getCustomerIdByTokenId();
        if (!$customerId && $this->helper->isGlobalScope()) {
            $customerId = $this->_getCustomerIdByTokenId(true);
        }

        return $customerId;
    }

    /**
     * Get customer id by token id
     *
     * @param bool $useGlobalScope
     * @return int|mixed
     */
    protected function _getCustomerIdByTokenId($useGlobalScope = false)
    {
        $customerId = 0;

        if ($this->getAccountData('token_id')) {
            $collection = $this->getCollection()
                ->join(['ce' => 'customer_entity'], 'ce.entity_id = main_table.customer_id', null)
                ->addFieldToFilter('main_table.type', $this->type)
                ->addFieldToFilter('main_table.token_id', $this->getAccountData('token_id'))
                ->setPageSize(1);

            if ($useGlobalScope == false) {
                $collection->addFieldToFilter('ce.website_id', $this->websiteId);
            }

            $customerId = $collection->getFirstItem()->getData('customer_id');
        }

        return $customerId;
    }

    /**
     * Get customer id by Email
     *
     * @return int
     */
    public function getCustomerIdByEmail()
    {
        $customerId = $this->_getCustomerIdByEmail();
        if (!$customerId && $this->helper->isGlobalScope()) {
            $customerId = $this->_getCustomerIdByEmail(true);
        }
        return $customerId;
    }

    /**
     * Get customer id by Email
     *
     * @param bool $useGlobalScope
     * @return int
     */
    protected function _getCustomerIdByEmail($useGlobalScope = false)
    {
        $customerId = 0;

        if (is_string($this->getAccountData('email'))) {
            $collection = $this->customer->getCollection()
                ->addFieldToFilter('email', $this->getAccountData('email'))
                ->setPageSize(1);

            if ($useGlobalScope == false) {
                $collection->addFieldToFilter('website_id', $this->websiteId);
            }

            $customerId = $collection->getFirstItem()->getId();
        }

        return $customerId;
    }

    /**
     * Register Account
     *
     * @return int
     */
    public function registerAccount()
    {
        $customerId = 0;
        $errors = [];
        $customer = $this->customer->setId(null);
        $storeId = $this->storeManager->getStore()->getId();

        try {
            $customer->setData($this->getAccountData())
                ->setData('is_active', 1);
            if ($this->getAccountData('password')) {
                $customer->setPassword($this->getAccountData('password'))
                    ->setPasswordConfirmation($this->getAccountData('password'));
            }
            $customer->getGroupId();
            if ($this->helper->showPassword()) {
                $customer->setData('showPassword', 1);
            }
            $customerId = $customer->save()->getId();
            $customer->setConfirmation(null)->save();

            if (strpos($this->getAccountData('email'), $this->type.'-user.com') === false) {
                $customer->sendNewAccountEmail('registered', '', $storeId);
            }
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }

        $this->setCustomer($customer);
        $this->setErrors($errors);

        return $customerId;
    }

    /**
     * Set customer photo
     *
     * @param int $customerId
     * @return bool|void
     */
    public function setCustomerPhoto($customerId)
    {
        $upload = false;

        $fileUrl = $this->getAccountData('photo');
        if (empty($fileUrl) || !is_numeric($customerId) || $customerId < 1) {
            return;
        }

        $tmpPath = $this->photoDir . DIRECTORY_SEPARATOR . $customerId .'.tmp';
        $io = $this->file;

        try {
            $io->mkdir($this->photoDir);
            if ($file = $this->_loadFile($fileUrl)) {
                if (file_put_contents($tmpPath, $file) > 0) {
                    $image = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Framework\Image', ['fileName' => $tmpPath]);
                    $image->resize(40);

                    $fileName = $customerId .'.png';
                    $image->save(null, $fileName);

                    $upload = true;
                }
            }
        } catch (\Exception $e) {
        }

        if ($io->fileExists($tmpPath)) {
            $io->rm($tmpPath);
        }

        return $upload;
    }

    /**
     * @param mixed $url
     * @param int $count
     * @return false|string
     */
    protected function _loadFile($url, $count = 1)
    {
        if ($count > 5) {
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$data) {
            return false;
        }

        $dataArray = explode("\r\n\r\n", $data, 2);
        if (count($dataArray) != 2) {
            return false;
        }

        list($header, $body) = $dataArray;
        if ($httpCode == 301 || $httpCode == 302) {
            $matches = [];
            preg_match('/Location:(.*?)\n/', $header, $matches);

            if (isset($matches[1])) {
                return $this->_loadFile(trim($matches[1]), $count++);
            }
        }
        return $body;
    }

    /**
     * @param $url
     * @param string $method
     * @param array $params
     * @param string $type
     * @return mixed
     * @throws \Magento\Framework\Validator\Exception
     * @throws \Zend_Http_Client_Exception
     */
    protected function _httpRequest($url, $method = 'GET', $params = [], $type = '')
    {
        $client = new \Laminas\Http\Client($url, ['timeout' => 60]);
        $method2 = $this->getMethod($client, $method, $params);
        $client->setMethod($method2);
        $response = $client->send($client->getRequest());
        $decodedResponse = json_decode($response->getBody());
        if (isset($decodedResponse->error) && is_object($decodedResponse->error)) {
            throw new \Magento\Framework\Validator\Exception(__($decodedResponse->error->message));
        }
        if (!$response->isSuccess()) {
            $status = $response->getStatusCode();
            if (($status == 400 || $status == 401)) {
                if (is_object($decodedResponse->error) && isset($decodedResponse->error->message)) {
                    $message = $decodedResponse->error->message;
                } else {
                    $message = __('Unspecified OAuth error occurred.');
                }
                throw new \Magento\Framework\Validator\Exception(__($message));
            } else {
                $message = sprintf('HTTP error %d occurred while issuing request.', $status);
                throw new \Magento\Framework\Validator\Exception(__($message));
            }
        }
        if ($this->check($type, $decodedResponse) == true) {
            $parsed_response = [];
            parse_str($response->getBody(), $parsed_response);
            $decodedResponse = json_decode(json_encode($parsed_response));
        }
        return $decodedResponse;
    }

    /**
     * @param $type
     * @param $decodedResponse
     * @return bool
     */
    protected function check($type, $decodedResponse)
    {
        if (empty($decodedResponse) && ($type == 'facebook' || $type == 'vkontakte')) {
            return true;
        }
        return false;
    }

    /**
     * @param $client
     * @param $method
     * @param $params
     * @return mixed
     * @throws \Magento\Framework\Validator\Exception
     */
    protected function getMethod(&$client, $method, $params)
    {
        switch ($method) {
            case 'GET':
                $client->setParameterGet($params);
                break;
            case 'POST':
                $client->setParameterPost($params);
                break;
            case 'DELETE':
                break;
            default:
                throw new \Magento\Framework\Validator\Exception(
                    __('Required HTTP method is not supported.')
                );
        }
        return $method;
    }
}
