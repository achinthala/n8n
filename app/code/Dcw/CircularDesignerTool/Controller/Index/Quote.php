<?php
declare(strict_types=1);

namespace Dcw\CircularDesignerTool\Controller\Index;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Quote extends Action
{
    public function __construct(
        Context $context,
        private readonly StateInterface $inlineTranslation,
        private readonly StoreManagerInterface $storeManager,
        private readonly TransportBuilder $transportBuilder,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ManagerInterface $manager,
        private readonly ResourceConnection $resource
    ) {
        parent::__construct($context);
    }
    
    public function execute()
    {
        $postData = $this->getRequest()->getPost();
        $connection = $this->resource->getConnection();
        $circularDesignerToolTable = $connection->getTableName("circular_designer_tool");

        $cdtFrmData = [
            'name' => $postData['name'],
            'school' => $postData['school'],
            'zipcode' => $postData['zip'],
            'phone' => $postData['phone'],
            'email' => $postData['email'],
            'heard_about' => $postData['howfound'],
            'comments' => $postData['comments'],
            'raw_designer_data' => $postData['raw_designer_data'],
        ];

        $connection->insert($circularDesignerToolTable, $cdtFrmData);
        $lastInsertId = $connection->lastInsertId();
        
        $templateCode = 'circle_design_tool_request_quote';
        $emailTemplateTable = $connection->getTableName("email_template");
        $emailTemplateSelectSql = $connection->select()
        ->from($emailTemplateTable, 'template_id')->where('orig_template_code = ?', $templateCode);
        $templateId = $connection->fetchOne($emailTemplateSelectSql);

        if (empty($templateId)) {
            $templateId = $templateCode;
        }
       
        $storeId = $this->storeManager->getStore()->getId();
        $senderName = $postData['name']; //store admin sender name
        $senderEmail = $postData['email']; // store admin email id
        $recipientEmail =  $this->scopeConfig->getValue(
            'trans_email/ident_sales/email',
            ScopeInterface::SCOPE_STORE,
            $storeId
        ); // recipient email id

        $recipientName =  $this->scopeConfig->getValue(
            'trans_email/ident_sales/name',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!$senderEmail && !$recipientEmail) {
            $this->manager->addErrorMessage(__("Something Went Wrong"));
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl('DesignerWrestlingMat');

            return $resultRedirect;
        }

        $this->inlineTranslation->suspend();
        $this->transportBuilder
            ->setTemplateIdentifier($templateId)
            ->setTemplateOptions([
                'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId,
            ])
            ->setTemplateVars(
                [
                    'cdt_id' => $lastInsertId,
                    'name' => $postData['name'],
                    'school' => $postData['school'],
                    'zip' => $postData['zip'],
                    'phone' => $postData['phone'],
                    'email' => $postData['email'],
                    'howfound' => $postData['howfound'],
                    'comments' => $postData['comments'],
                    'raw_designer_data' => $postData['raw_designer_data'],
                    //'raw_designer_data_encode' => $this->urlEncoder->encode($postData['raw_designer_data']),
                    'raw_designer_data_encode' => base64_encode($postData['raw_designer_data']),
                    'store' => $this->storeManager->getStore(),
                ]
            );
   
            $this->transportBuilder
            ->setFrom([
                'name'  => $senderName,
                'email' => $senderEmail,
            ])
            ->addTo($recipientEmail, $recipientName);
        /* @var \Magento\Framework\Mail\Transport $transport */
        $transport = $this->transportBuilder->getTransport();

        try {
            $transport->sendMessage();
        } catch (Exception $e) {
            $this->context->getLogger()->alert($e->getMessage());
        } finally {
            $this->inlineTranslation->resume();
        }
        
        $this->manager->addSuccessMessage(__("Your design submitted successfully. We will get back to you shortly."));
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl('/DesignerWrestlingMat');
        return $resultRedirect;
    }
}
