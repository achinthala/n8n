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

use Laminas\Http\Request as HttpRequest;
use Laminas\OAuth\Consumer;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class Twitter
 *
 * @package Bss\SocialLogin\Model
 * @codingStandardsIgnoreFile
 */
class Twitter extends \Bss\SocialLogin\Model\SocialLogin
{
    const URL_AUTHORIZE = 'https://api.twitter.com/oauth/authorize';

    const OAUTH_URI = 'https://api.twitter.com/oauth';

    const OAUTH2_SERVICE_URI = 'https://api.twitter.com/1.1';

    /**
     * @var string
     */
    protected $type = 'twitter';

    /**
     * @var string[]
     */
    protected $response_type = ['oauth_token', 'oauth_verifier'];

    /**
     * @var null
     */
    protected $token = null;

    /**
     * @var array
     */
    protected $fields = [
        'token_id' => 'id',
        'firstname' => 'firstname',
        'lastname' => 'lastname',
        'email' => 'email',
        'dob' => null,
        'gender' => null,
        'photo' => 'profile_image_url',
    ];
    /**
     * @var null
     */
    protected $authUrl = null;

    /**
     * @var array
     */
    protected $popupSize = [630, 650];

    /**
     * @var SerializerInterface
     */
    protected $serialize;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected \Magento\Customer\Model\Session $session;

    /**
     * @inheritdoc
     */
    public function _construct()
    {
        parent::_construct();
    }

    /**
     * @param SerializerInterface $serializer
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Bss\SocialLogin\Helper\Data $helper
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        SerializerInterface $serializer,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Customer $customer,
        \Bss\SocialLogin\Helper\Data $helper,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\Math\Random $random,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $customer,
            $helper,
            $storeManager,
            $filesystem,
            $encryptor,
            $random,
            $file,
            $resource,
            $resourceCollection,
            $data
        );
        $this->serialize = $serializer;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
        $this->encryptor = $encryptor;
        $this->session = $session;
    }

    /**
     * @return |null
     */
    public function getButtonUrl()
    {
        $token = $this->createAuthUrl();
        if (!empty($token)) {
            $this->authUrl = self::URL_AUTHORIZE . '?oauth_token=' . $token;
        }
        return parent::getButtonUrl();
    }

    /**
     * @param $response
     * @return bool
     * @throws \Magento\Framework\Validator\Exception
     */
    public function loadAccountInfo($response)
    {
        $client = new Consumer(
            [
                'callbackUrl' => $this->redirectUri,
                'siteUrl' => self::OAUTH_URI,
                'authorizeUrl' => self::OAUTH_URI . '/authenticate',
                'consumerKey' => $this->applicationId,
                'consumerSecret' => $this->secret
            ]
        );

        if ($requesttk = $this->session->getRequestToken()) {
            $this->token = $client->getAccessToken(
                $response,
                unserialize($requesttk)
            );

            $url = self::OAUTH2_SERVICE_URI . '/account/verify_credentials.json';
            $params = ['include_email' => 'true', 'include_entities' => 'false', 'skip_status' => 'true'];

            if ($data = $this->_httpRequest($url, 'GET', $params)) {
                $data = json_decode(json_encode($data), true);
            }

            if (!$this->accountData = $this->_filterData($data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $url
     * @param string $method
     * @param array $params
     * @param string $type
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws \Magento\Framework\Validator\Exception
     */
    protected function _httpRequest($url, $method = 'GET', $params = [], $type = '')
    {
        $client = $this->token->getHttpClient(
            [
                'callbackUrl' => $this->redirectUri,
                'siteUrl' => self::OAUTH_URI,
                'consumerKey' => $this->applicationId,
                'consumerSecret' => $this->secret
            ]
        );

        $client->setUri($url);

        switch ($method) {
            case 'GET':
                $client->setMethod(HttpRequest::METHOD_GET);
                $client->setParameterGet($params);
                break;
            case 'POST':
                $client->setMethod(HttpRequest::METHOD_POST);
                $client->setParameterPost($params);
                break;
            case 'DELETE':
                $client->setMethod(HttpRequest::METHOD_DELETE);
                break;
            default:
                throw new \Magento\Framework\Validator\Exception(__('Required HTTP method is not supported.'));
        }

        $response = $client->request();

        $decoded_response = json_decode($response->getBody());

        if ($response->isError()) {
            $status = $response->getStatus();
            if (($status == 400 || $status == 401 || $status == 429)) {
                if (isset($decoded_response->error->message)) {
                    $message = $decoded_response->error->message;
                } else {
                    $message = __('Unspecified OAuth error occurred.');
                }
                throw new \Magento\Framework\Validator\Exception(__($message));
            } else {
                $message = sprintf('HTTP error %d occurred while issuing request.', $status);
                throw new \Magento\Framework\Validator\Exception(__($message));
            }
        }

        return $decoded_response;
    }

    /**
     * Create Auth url
     *
     * @return string
     * @throws \Magento\Framework\Validator\Exception
     */
    protected function createAuthUrl()
    {
        $config = [
            'callbackUrl'=>$this->redirectUri,
            'siteUrl' => 'https://api.twitter.com/oauth',
            'consumerKey'=>$this->applicationId,
            'consumerSecret'=>$this->secret
        ];
        $oauth = new Consumer($config);

        try {
            $request_token = $oauth->getRequestToken();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Validator\Exception(__($e->getMessage()));
        }

        $this->session->setRequestToken($this->serialize->serialize($request_token));

        $exploded_request_token = explode('=', str_replace('&', '=', $request_token));

        $oauth_token = $exploded_request_token[1];

        return $oauth_token;
    }

    /**
     * @param $data
     * @return array|bool
     */
    protected function _filterData($data)
    {
        if (empty($data['id'])) {
            return false;
        }

        if (!empty($data['name'])) {
            $nameParts = explode(' ', $data['name'], 2);
            $data['firstname'] = $nameParts[0];
            $data['lastname'] = !empty($nameParts[1]) ? $nameParts[1] : '';
        }

        return parent::_filterData($data);
    }
}
