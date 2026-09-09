<?php

declare(strict_types=1);

namespace Dcw\TaxCertificate\Model;

use Exception;
use Magento\Framework\Mail\Template\TransportBuilder;
use Dcw\TaxCertificate\Model\ResourceModel\Certificate as CertificateResource;
use Avalara\AvaTax\Block\ViewModel\CustomerCertificates as CustomerCertificatesViewModel;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Customer\Model\Customer;
use Avalara\AvaTax\Model\Certificates;
use Magento\Variable\Model\Variable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Area;
use Magento\Store\Model\Store;
use Zend_Log;
use Zend_Log_Writer_Stream;

/**
 * Certificate Processor
 * 
 * Processes tax certificates for customers, synchronizes certificate statuses,
 * and sends notification emails when certificate statuses change.
 */
class CertificateProcessor
{
    /**
     * Certificate status constants
     */
    private const STATUS_APPROVED = 'APPROVED';
    private const STATUS_DENIED = 'DENIED';
    private const STATUS_PENDING = 'PENDING';

    /**
     * Custom variable codes for email templates
     */
    private const VAR_APPROVED_TEMPLATE = 'approved_template_id';
    private const VAR_DENIED_TEMPLATE = 'denied_template_id';
    private const VAR_PENDING_TEMPLATE = 'pending_template_id';

    /**
     * Email configuration
     */
    private const EMAIL_FROM_EMAIL = 'support@flooringinc.com';
    private const EMAIL_FROM_NAME = 'FlooringInc';
    private const LOG_FILE = 'CronTaxCertificate.log';

    /**
     * @var CertificateResource
     */
    protected $certificateResource;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var CustomerCertificatesViewModel
     */
    protected $customerCertificatesViewModel;

    /**
     * @var CustomerCollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var Certificates
     */
    protected $certificatesModel;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Variable
     */
    protected $variable;

    /**
     * @var Zend_Log|null
     */
    private $logger = null;

    /**
     * @param CertificateResource $certificateResource
     * @param TransportBuilder $transportBuilder
     * @param CustomerCertificatesViewModel $customerCertificatesViewModel
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param Certificates $certificatesModel
     * @param ScopeConfigInterface $scopeConfig
     * @param Variable $variable
     */
    public function __construct(
        CertificateResource $certificateResource,
        TransportBuilder $transportBuilder,
        CustomerCertificatesViewModel $customerCertificatesViewModel,
        CustomerCollectionFactory $customerCollectionFactory,
        Certificates $certificatesModel,
        ScopeConfigInterface $scopeConfig,
        Variable $variable
    ) {
        $this->certificateResource = $certificateResource;
        $this->transportBuilder = $transportBuilder;
        $this->customerCertificatesViewModel = $customerCertificatesViewModel;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->certificatesModel = $certificatesModel;
        $this->scopeConfig = $scopeConfig;
        $this->variable = $variable;
    }

    /**
     * Main processing method - processes all customers and their certificates
     * 
     * @return void
     */
    public function process(): void
    {
        $this->logInfo('Certificate processing started - Beginning batch processing of all customers');

        try {
            $customers = $this->customerCollectionFactory->create();
            $totalCustomers = $customers->getSize();
            $this->logInfo(
                "Customer collection loaded successfully - Total customers to process: {$totalCustomers}"
            );
        } catch (Exception $e) {
            $this->logError(
                "Failed to load customer collection - Error: {$e->getMessage()}",
                ['exception' => $e->getTraceAsString()]
            );
            return;
        }

        $processedCount = 0;
        $errorCount = 0;

        foreach ($customers as $customer) {
            $customerId = (int)$customer->getId();
            $customerEmail = $customer->getEmail() ?? 'N/A';
            $this->logInfo(
                "Processing customer - Customer ID: {$customerId}, Email: {$customerEmail}"
            );

            try {
                // getCertificatesList() makes an API call to Avalara AvaTax Document Management API
                // It fetches tax exemption certificates for the given customer ID
                // The method internally calls: Avalara REST API -> GET /customers/{customerId}/certificates
                // Returns: Collection of certificate objects with data like:
                //   - id: Certificate ID
                //   - status: APPROVED, DENIED, or PENDING
                //   - valid: boolean indicating if certificate is valid
                //   - signed_date: Date certificate was signed
                //   - expiration_date: Certificate expiration date
                //   - exposure_zone: Tax exposure zone information
                
                $certificates = $this->certificatesModel->getCertificatesList($customerId);
                $certificateCount = is_countable($certificates) ? count($certificates) : 0;

                // Log the full Avalara API response for reference
                $responseData = [];
                if (!empty($certificates)) {
                    foreach ($certificates as $index => $cert) {
                        if (is_object($cert) && method_exists($cert, 'getData')) {
                            $responseData[$index] = $cert->getData();
                        } elseif (is_object($cert)) {
                            // Try to convert object to array
                            $responseData[$index] = json_decode(json_encode($cert), true);
                        } else {
                            $responseData[$index] = $cert;
                        }
                    }
                }

                // Log full response data as JSON for easy reference
                $responseJson = json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $this->logInfo(
                    "Avalara API full response data - Customer ID: {$customerId}, Response JSON: {$responseJson}"
                );

                if (empty($certificates)) {
                    $this->logInfo("No certificates found for customer - Customer ID: {$customerId}");
                    continue;
                }

                foreach ($certificates as $certificate) {
                    // Each certificate is a data object containing certificate information from Avalara
                    // Access certificate data using getData() method
                    $certificateId = $certificate->getData('id') ?? 'N/A';
                    $certificateStatus = $certificate->getData('status') ?? 'N/A';
                    $certificateValid = $certificate->getData('valid') ? 'true' : 'false';
                    
                    $this->logInfo(
                        "Processing individual certificate from Avalara - Customer ID: {$customerId}, " .
                        "Certificate ID: {$certificateId}, Status: {$certificateStatus}, Valid: {$certificateValid}"
                    );
                    
                    $this->processCertificate($customer, $certificate);
                }

                $processedCount++;
            } catch (Exception $e) {
                $errorCount++;
                $this->logError(
                    "Failed to process certificates for customer - Customer ID: {$customerId}, Error: {$e->getMessage()}",
                    ['exception' => $e->getTraceAsString()]
                );
            }
        }

        $this->logInfo(
            "Certificate processing completed - Processed: {$processedCount} customers, Errors: {$errorCount}, Total: {$totalCustomers}"
        );
    }

    /**
     * Process a single certificate for a customer
     * 
     * Handles certificate status synchronization, database updates, and email notifications
     * 
     * @param Customer $customer
     * @param mixed $certificate
     * @return void
     */
    protected function processCertificate(Customer $customer, $certificate): void
    {
        $customerId = (int)$customer->getId();
        $certificateId = $certificate->getData('id');
        
        $certId = $certificateId ?? 'N/A';
        $this->logInfo(
            "Starting certificate processing - Customer ID: {$customerId}, Certificate ID: {$certId}"
        );

        $status = $this->determineCertificateStatus($certificate);
        
        $certId = $certificateId ?? 'N/A';
        $this->logInfo(
            "Certificate status determined - Customer ID: {$customerId}, Certificate ID: {$certId}, Status: {$status}"
        );

        $existingRecord = $this->certificateResource->fetchByCustomerAndCertificateId(
            $customerId,
            $certificateId
        );

        if ($existingRecord) {
            $this->handleExistingCertificate($customer, $certificate, $existingRecord, $status);
        } else {
            $this->handleNewCertificate($customer, $certificate, $certificateId, $status);
        }
    }

    /**
     * Handle processing for an existing certificate record
     * 
     * @param Customer $customer
     * @param mixed $certificate
     * @param array $existingRecord
     * @param string $status
     * @return void
     */
    protected function handleExistingCertificate(
        Customer $customer,
        $certificate,
        array $existingRecord,
        string $status
    ): void {
        $customerId = (int)$customer->getId();
        $certificateId = $certificate->getData('id');
        $existingStatus = $existingRecord['status'] ?? 'N/A';
        $emailFlag = (int)($existingRecord['email_flag'] ?? 0);

        $certId = $certificateId ?? 'N/A';
        $this->logInfo(
            "Processing existing certificate record - Customer ID: {$customerId}, Certificate ID: {$certId}, " .
            "Existing Status: {$existingStatus}, New Status: {$status}, Email Flag: {$emailFlag}"
        );

        $emailIsEnabled = false;

        if ($existingStatus !== $status) {
            // Status has changed - update record and send email
            $certId = $certificateId ?? 'N/A';
            $this->logInfo(
                "Certificate status changed - Customer ID: {$customerId}, Certificate ID: {$certId}, " .
                "Old Status: {$existingStatus}, New Status: {$status} - Sending notification email"
            );

            //$this->sendEmail($customer, $certificate, $status); //Stop the email from being sent for existing certificates
			if($status == 'PENDING'){
				$this->sendEmailToAdmin($customer, $certificate, $status);
			}
            
            $this->certificateResource->updateRecord([
                'id' => $existingRecord['id'],
                'status' => $status,
                'email_flag' => 1 // Reset email flag to ensure email is only sent once for this status change
            ]);

            $this->logInfo(
                "Certificate record updated successfully - Record ID: {$existingRecord['id']}, New Status: {$status}"
            );
        } elseif ($emailFlag === 0 && $emailIsEnabled) {
            // Status is the same but email wasn't sent - send it now and update the flag
            $certId = $certificateId ?? 'N/A';
            $this->logInfo(
                "Certificate status unchanged but email not sent - Customer ID: {$customerId}, " .
                "Certificate ID: {$certId}, Status: {$status} - Sending notification email"
            );

            //$this->sendEmail($customer, $certificate, $status); //Stop the email from being sent for existing certificates
			if($status == 'PENDING'){
				$this->sendEmailToAdmin($customer, $certificate, $status);
			}
            $this->certificateResource->updateFlag($existingRecord['id'], 1);

            $this->logInfo("Email flag updated successfully - Record ID: {$existingRecord['id']}");
        } else if ($emailIsEnabled) {
            $certId = $certificateId ?? 'N/A';
            $this->logInfo(
                "Certificate record already processed - Customer ID: {$customerId}, Certificate ID: {$certId}, " .
                "Status: {$status}, Email already sent"
            );
        }
    }

    /**
     * Handle processing for a new certificate record
     * 
     * @param Customer $customer
     * @param mixed $certificate
     * @param string|int|null $certificateId
     * @param string $status
     * @return void
     */
    protected function handleNewCertificate(
        Customer $customer,
        $certificate,
        $certificateId,
        string $status
    ): void {
        $customerId = (int)$customer->getId();

        $certId = $certificateId ?? 'N/A';
        $this->logInfo(
            "Processing new certificate - Customer ID: {$customerId}, Certificate ID: {$certId}, Status: {$status}"
        );

        try {
            //$this->sendEmail($customer, $certificate, $status); //Stop the email from being sent for new certificates
			
			if($status == 'PENDING'){
				$this->sendEmailToAdmin($customer, $certificate, $status);
			}
            
            $this->certificateResource->insertRecord([
                'customer_id' => $customerId,
                'certificate_id' => $certificateId,
                'email_flag' => 1,
                'status' => $status
            ]);

            $certId = $certificateId ?? 'N/A';
            $this->logInfo(
                "New certificate record created successfully - Customer ID: {$customerId}, Certificate ID: {$certId}, Status: {$status}"
            );
        } catch (Exception $e) {
            $certId = $certificateId ?? 'N/A';
            $this->logError(
                "Failed to create new certificate record - Customer ID: {$customerId}, Certificate ID: {$certId}, Error: {$e->getMessage()}",
                ['exception' => $e->getTraceAsString()]
            );
        }
    }

    /**
     * Determines the status of the certificate (Pending, Approved, Denied)
     * 
     * @param mixed $certificate
     * @return string
     */
    protected function determineCertificateStatus($certificate): string
    {
        $rawStatus = $certificate->getData('status');
        $validStatus = $certificate->getData('valid');
        
        $rawStatusStr = $rawStatus ?? 'N/A';
        $validStatusStr = $validStatus !== null ? ($validStatus ? 'true' : 'false') : 'N/A';
        $this->logInfo(
            "Determining certificate status - Raw Status: {$rawStatusStr}, Valid: {$validStatusStr}"
        );

        return $rawStatus ?? self::STATUS_PENDING;
    }

    /**
     * Send email notification to customer about certificate status
     * 
     * @param Customer $customer
     * @param mixed $certificate
     * @param string $status
     * @return void
     */
    protected function sendEmail(Customer $customer, $certificate, string $status): void
    {
        $customerId = (int)$customer->getId();
        $customerEmail = $customer->getEmail();
        $certificateId = $certificate->getData('id');
        $certificateStatus = $certificate->getData('status') ?? 'N/A';

        $customerEmailStr = $customerEmail ?? 'N/A';
        $certId = $certificateId ?? 'N/A';
        $this->logInfo(
            "Preparing to send certificate status email - Customer ID: {$customerId}, Email: {$customerEmailStr}, " .
            "Certificate ID: {$certId}, Status: {$certificateStatus}"
        );

        $adminEmail = $this->scopeConfig->getValue(
            'trans_email/ident_general/email',
            ScopeInterface::SCOPE_STORE
        );

        $adminName = $this->scopeConfig->getValue(
            'trans_email/ident_general/name',
            ScopeInterface::SCOPE_STORE
        );

        $adminEmailStr = $adminEmail ?? 'N/A';
        $adminNameStr = $adminName ?? 'N/A';
        $this->logInfo(
            "Email configuration loaded - Admin Email: {$adminEmailStr}, Admin Name: {$adminNameStr}"
        );

        $templateId = $this->getEmailTemplateId($status);
        
        if (empty($templateId)) {
            $this->logError(
                "Email template ID not found for status - Status: {$status}, Customer ID: {$customerId}"
            );
            return;
        }

        $this->logInfo("Email template determined - Status: {$status}, Template ID: {$templateId}");

        $customerName = $customer->getName() ?? '';
        $firstName = $this->extractFirstName($customerName);

        $customerNameStr = $customerName ?: 'N/A';
        $firstNameStr = $firstName ?: 'N/A';
        $this->logInfo(
            "Customer name processed - Full Name: {$customerNameStr}, First Name: {$firstNameStr}"
        );

        $emailVars = [
            'customer_name' => $customerName,
            'firstname' => $firstName,
            'status' => $status
        ];

        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateVars($emailVars)
                ->setFrom([
                    'email' => self::EMAIL_FROM_EMAIL,
                    'name' => self::EMAIL_FROM_NAME
                ])
                ->addTo($customerEmail)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => Store::DEFAULT_STORE_ID
                ])
                ->getTransport();

            $transport->sendMessage();

            $customerEmailStr = $customerEmail ?? 'N/A';
            $this->logInfo(
                "Certificate status email sent successfully - Customer ID: {$customerId}, Email: {$customerEmailStr}, Status: {$status}"
            );
        } catch (Exception $e) {
            $customerEmailStr = $customerEmail ?? 'N/A';
            $this->logError(
                "Failed to send certificate status email - Customer ID: {$customerId}, Email: {$customerEmailStr}, Error: {$e->getMessage()}",
                ['exception' => $e->getTraceAsString()]
            );
        }
    }
	
	/**
     * Send email notification to Admin
     * 
     * @param Customer $customer
     * @param mixed $certificate
     * @param string $status
     * @return void
     */
    protected function sendEmailToAdmin(Customer $customer, $certificate, string $status): void
    {
		$notifyAdminEmail = $this->scopeConfig->getValue(
            'certificates_notification/general/taxadminemail',
            ScopeInterface::SCOPE_STORE
        );
        $customerId = (int)$customer->getId();
        $customerEmail = $customer->getEmail();
        $certificateId = $certificate->getData('id');
        $certificateStatus = $certificate->getData('status') ?? 'N/A';

        $customerEmailStr = $customerEmail ?? 'N/A';
        $certId = $certificateId ?? 'N/A';
        $this->logInfo(
            "Preparing to send certificate status email - Customer ID: {$customerId}, Email: {$customerEmailStr}, " .
            "Certificate ID: {$certId}, Status: {$certificateStatus}"
        );

        $adminEmail = $this->scopeConfig->getValue(
            'trans_email/ident_general/email',
            ScopeInterface::SCOPE_STORE
        );

        $adminName = $this->scopeConfig->getValue(
            'trans_email/ident_general/name',
            ScopeInterface::SCOPE_STORE
        );

        $adminEmailStr = $adminEmail ?? 'N/A';
        $adminNameStr = $adminName ?? 'N/A';
        $this->logInfo(
            "Email configuration loaded - Admin Email: {$adminEmailStr}, Admin Name: {$adminNameStr}"
        );

        $templateId = $this->getEmailTemplateId($status);
        
        if (empty($templateId)) {
            $this->logError(
                "Email template ID not found for status - Status: {$status}, Customer ID: {$customerId}"
            );
            return;
        }

        $this->logInfo("Email template determined - Status: {$status}, Template ID: {$templateId}");

        $customerName = $customer->getName() ?? '';
        $firstName = $this->extractFirstName($customerName);

        $customerNameStr = $customerName ?: 'N/A';
        $firstNameStr = $firstName ?: 'N/A';
        $this->logInfo(
            "Customer name processed - Full Name: {$customerNameStr}, First Name: {$firstNameStr}"
        );

        $emailVars = [
            'customer_name' => $customerName,
            'firstname' => $firstName,
			'email' => $customerEmailStr,
            'status' => $status
        ];
		$bccEmail='shailendra@dotcomweavers.com';

        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateVars($emailVars)
                ->setFrom([
                    'email' => self::EMAIL_FROM_EMAIL,
                    'name' => self::EMAIL_FROM_NAME
                ])
                ->addTo($notifyAdminEmail)
				->addBcc($bccEmail)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => Store::DEFAULT_STORE_ID
                ])
                ->getTransport();

            $transport->sendMessage();

            $customerEmailStr = $customerEmail ?? 'N/A';
            $this->logInfo(
                "Certificate status email sent to admin successfully - Customer ID: {$customerId}, Email: {$customerEmailStr}, Status: {$status}"
            );
        } catch (Exception $e) {
            $customerEmailStr = $customerEmail ?? 'N/A';
            $this->logError(
                "Failed to send certificate status email - Customer ID: {$customerId}, Email: {$customerEmailStr}, Error: {$e->getMessage()}",
                ['exception' => $e->getTraceAsString()]
            );
        }
    }

    /**
     * Get email template ID based on certificate status
     * 
     * @param string $status
     * @return string|null
     */
    protected function getEmailTemplateId(string $status): ?string
    {
        switch ($status) {
            case self::STATUS_APPROVED:
                $variableCode = self::VAR_APPROVED_TEMPLATE;
                break;
            case self::STATUS_DENIED:
                $variableCode = self::VAR_DENIED_TEMPLATE;
                break;
            case self::STATUS_PENDING:
            default:
                $variableCode = self::VAR_PENDING_TEMPLATE;
                break;
        }

        $templateId = $this->variable->loadByCode($variableCode)->getPlainValue();
        
        return $templateId ?: null;
    }

    /**
     * Extract first name from full name
     * 
     * @param string $fullName
     * @return string
     */
    protected function extractFirstName(string $fullName): string
    {
        if (empty($fullName)) {
            return '';
        }

        $nameParts = explode(' ', trim($fullName));
        return $nameParts[0] ?? '';
    }

    /**
     * Get or create logger instance
     * 
     * @return Zend_Log
     */
    protected function getLogger(): Zend_Log
    {
        if ($this->logger === null) {
            $writer = new Zend_Log_Writer_Stream(BP . '/var/log/' . self::LOG_FILE);
            $this->logger = new Zend_Log();
            $this->logger->addWriter($writer);
        }

        return $this->logger;
    }

    /**
     * Log informational message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $logMessage = $this->formatLogMessage($message, $context);
        $this->getLogger()->info($logMessage);
    }

    /**
     * Log error message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        $logMessage = $this->formatLogMessage($message, $context);
        $this->getLogger()->err($logMessage);
    }

    /**
     * Format log message with context
     * 
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function formatLogMessage(string $message, array $context = []): string
    {
        if (empty($context)) {
            return $message;
        }

        $contextString = json_encode($context, JSON_UNESCAPED_SLASHES);
        return "{$message} | Context: {$contextString}";
    }

    /**
     * Legacy method for backward compatibility
     * 
     * @deprecated Use logInfo() or logError() instead
     * @param string $msg
     * @return void
     */
    public function createLog(string $msg): void
    {
        $this->logInfo($msg);
    }
}
