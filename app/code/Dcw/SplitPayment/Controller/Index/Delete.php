<?php
namespace Dcw\SplitPayment\Controller\Index;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;

class Delete extends \ParadoxLabs\TokenBase\Controller\Paymentinfo
{
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Registry $registry,
        \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory,
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \ParadoxLabs\TokenBase\Helper\Address $addressHelper,
        \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Serialize\Serializer\Json $json
    ) {
        $this->paymentFactory   = $paymentFactory;
        $this->checkoutSession  = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->_json = $json;

        parent::__construct(
            $context,
            $customerSession,
            $resultPageFactory,
            $formKeyValidator,
            $registry,
            $cardFactory,
            $cardRepository,
            $helper,
            $addressHelper
        );
    }
    
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $id     = $this->getRequest()->getParam('id');
        $method = $this->getRequest()->getParam('method');

        if ($this->formKeyIsValid() === true && $this->methodIsValid() === true && !empty($id)) {
            try {
                /**
                 * Load the card and verify we are actually the cardholder before doing anything.
                 */

                /** @var \ParadoxLabs\TokenBase\Model\Card $card */
                $card = $this->cardRepository->getByHash($id);
                $card = $card->getTypeInstance();

                if ($card && $card->getHash() == $id && $card->hasOwner($this->helper->getCurrentCustomer()->getId())) {
                    $card->queueDeletion();
                    $card = $this->cardRepository->save($card);
                    return $result->setData([
                        'success' => 1,
                        'id'=>$card->getHash(),
                        'msg'=>__('Payment record deleted.')
                    ]);
                } else {
                    return $result->setData(['success' => 0,'msg'=>__('Invalid Request.')]);

                }
            } catch (\Exception $e) {
                $this->helper->log($method, (string)$e);
                return $result->setData(['success' => 0,'msg'=>__($e->getMessage())]);
            }
        } else {
            return $result->setData(['success' => 0,'msg'=>__('Invalid Request.')]);
        }
    }
}
