<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Dcw\CustomCompany\Controller\Company;

use Magento\LoginAsCustomerAssistance\Api\SetAssistanceInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Company\Model\Customer\Company as MagentoCompany;
use Dcw\CustomCompany\Model\Customer\IsSuperUser;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\CustomerExtractor;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Create company account action.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class CreatePost extends \Magento\Company\Controller\Account\CreatePost
{
    /**
     * @var string
     */
    private $formId = 'company_create';

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    private $objectHelper;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formKeyValidator;

    /**
     * @var \Magento\Company\Model\Action\Validator\Captcha
     */
    private $captchaValidator;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    private $userContext;

    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    private $customerAccountManagement;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory
     */
    private $customerDataFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Company\Model\Create\Session
     */
    private $companyCreateSession;

    /**
     * @var \Magento\Company\Model\CompanyUser|null
     */
    private $companyUser;
	
	protected $customerRegistry;
	
	protected $encryptor;
	
	protected $setAssistance;
	
	protected $resourceConnection;

    /**
     * @var CustomerExtractor
     */
    private $customerExtractor;

    /**
     * @var MagentoCompany
     */
    private $customerCompany;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var IsSuperUser
     */
    private $isSuperUser;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Api\DataObjectHelper $objectHelper
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Company\Model\Action\Validator\Captcha $captchaValidator
     * @param \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory
     * @param \Magento\Company\Model\Create\Session $companyCreateSession
     * @param \Magento\Company\Model\CompanyUser|null $companyUser
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Authorization\Model\UserContextInterface $userContext,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Api\DataObjectHelper $objectHelper,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Company\Model\Action\Validator\Captcha $captchaValidator,
        \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Company\Model\Create\Session $companyCreateSession,
        \Magento\Company\Model\CompanyUser $companyUser = null,
		CustomerRepositoryInterface $customerRepository,
		\Magento\Customer\Model\CustomerRegistry $customerRegistry,
		\Magento\Framework\Encryption\EncryptorInterface $encryptor,
		SetAssistanceInterface $setAssistance,
		ResourceConnection $resourceConnection,
        MagentoCompany $customerCompany = null,
        CustomerExtractor $customerExtractor = null,
        IsSuperUser $isSuperUser = null
    ) {
        parent::__construct($context, $userContext, $logger, $objectHelper, $formKeyValidator, $captchaValidator, $customerAccountManagement, $customerDataFactory, $companyCreateSession, $companyUser);
        $this->userContext = $userContext;
        $this->logger = $logger;
        $this->objectHelper = $objectHelper;
        $this->formKeyValidator = $formKeyValidator;
        $this->captchaValidator = $captchaValidator;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerDataFactory = $customerDataFactory;
        $this->companyCreateSession = $companyCreateSession;
        $this->companyUser = $companyUser ?:
            ObjectManager::getInstance()->get(\Magento\Company\Model\CompanyUser::class);
		$this->customerRepository = $customerRepository;
		$this->customerRegistry = $customerRegistry;
		$this->encryptor = $encryptor;
		$this->setAssistance = $setAssistance; 
		$this->resourceConnection = $resourceConnection;
        $this->customerCompany = $customerCompany
            ?: ObjectManager::getInstance()->get(MagentoCompany::class);
        $this->customerExtractor = $customerExtractor ?:
            ObjectManager::getInstance()->get(\Magento\Customer\Model\CustomerExtractor::class);
        $this->isSuperUser = $isSuperUser ?:
            ObjectManager::getInstance()->get(IsSuperUser::class);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $request = $this->getRequest();
		
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('*/account/create');

        if (!$this->validateRequest()) {
            return $resultRedirect;
        }

        try {
            if ($this->checkIfLoggedCustomerIsACompanyMember()) {
                /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
                $resultForward = $this->resultFactory
                    ->create(\Magento\Framework\Controller\ResultFactory::TYPE_FORWARD);
                $resultForward->setModule('company');
                $resultForward->setController('accessdenied');
                $resultForward->forward('index');
                return $resultForward;
            }

            $customerRequest = clone $request;
            $customerRequest->setParams((array)$request->getPost('customer', []));
            $customer = $this->createCompanyAndReturnSuperUser(
                $this->customerExtractor->extract('customer_account_create', $customerRequest)
            );
            $this->companyCreateSession->setCustomerId($customer->getId());

            $connection = $this->resourceConnection->getConnection();
            $table = $connection->getTableName('login_as_customer_assistance_allowed');
            $customerId = $customer->getId();
            $csdata = ['customer_id' => $customerId];
            $select = $connection->select()
                ->from($table)
                ->where('customer_id = ?', $customerId);

            $result = $connection->fetchRow($select);

            if (!$result) {
                $connection->insert($table, $csdata);
            }

            $getCurrentUserId = $this->userContext->getUserId();

            if ($getCurrentUserId) {
                $this->messageManager->addSuccessMessage(
                    __('Thank you for completing your profile.')
                );
            } else {
                $this->messageManager->addSuccessMessage(
                    __('Thank you! We\'re reviewing your request and will contact you soon')
                );
            }
            $resultRedirect->setPath('*/account/index');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred on the server. Your changes have not been saved.')
            );
            $this->logger->critical($e);
        }

        return $resultRedirect;
    }

    /**
     * Create superuser account if does not exist, then create a company (also see afterCreateAccount plugin)
     *
     * @param CustomerInterface $customerData
     * @return CustomerInterface
     * @throws LocalizedException
     * @throws CouldNotSaveException
     * @throws InputException
     */
    private function createCompanyAndReturnSuperUser(CustomerInterface $customerData): CustomerInterface
    {
        try {
            $customer = $this->customerRepository->get($customerData->getEmail());
        } catch (NoSuchEntityException $exception) {
            return $this->customerAccountManagement->createAccount($customerData);
        }
        if ($this->isSuperUser->execute($customer)) {
            throw new LocalizedException(
                __('This customer is a user of a different company. Enter a different email address to continue.')
            );
        }
        $company = $this->customerCompany->createCompany($customer, $this->getRequest()->getPost('company', []));
        $this->companyCreateSession->setNewCompanyId($company->getId());
        return $customer;
    }

    /**
     * Validate request
     *
     * @return bool
     */
    private function validateRequest(): bool
    {
        if (!$this->getRequest()->isPost()) {
            return false;
        }

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return false;
        }

        if (!$this->captchaValidator->validate($this->formId, $this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Incorrect CAPTCHA'));
            return false;
        }

        return true;
    }

    /**
     * Method checks if logged customer is a company customer
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function checkIfLoggedCustomerIsACompanyMember(): bool
    {
        try {
            return (bool)$this->companyUser->getCurrentCompanyId();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
    }
}
