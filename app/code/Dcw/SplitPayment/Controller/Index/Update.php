<?php
namespace Dcw\SplitPayment\Controller\Index;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;

class Update extends \ParadoxLabs\TokenBase\Controller\Paymentinfo
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

        $post = $this->getRequest()->getPost();

        $id     = $this->getRequest()->getParam('id');
        $method = $this->getRequest()->getParam('method');

        if ($this->formKeyIsValid() === true && $this->methodIsValid() === true && !empty($id)) {
            try {
                $card = $this->cardRepository->getByHash($id);
                $card = $card->getTypeInstance();
               /* ========================== save or update in split_payment_stored_card table  START ============== */
                $split_payment_stored_card_table = $this->connection->getTableName("split_payment_stored_card");
                $select = $this->connection->select()
                ->from($split_payment_stored_card_table, 'paradoxlabs_stored_card_id')
                ->where('paradoxlabs_stored_card_id = ?', $card->getId());
                $paradoxlabs_stored_card_id = $this->connection->fetchOne($select);

                if (!empty($paradoxlabs_stored_card_id)) {
                    $where = ['paradoxlabs_stored_card_id = ?' => $paradoxlabs_stored_card_id];
                    $this->connection->update(
                        $split_payment_stored_card_table,
                        ['payment_amount' => $post['cc_payment']],
                        $where
                    );
                } else {
                    $split_card_insert_data = [
                       'paradoxlabs_stored_card_id' => $card->getId(),
                       'payment_amount' => $post['cc_payment'],
                    ];
                    $this->connection->insert($split_payment_stored_card_table, $split_card_insert_data);
                }
                return $result->setData([
                    'success' => 1,
                    'id'=>$card->getHash(),
                    'msg'=>__('Payment data updated successfully.')
                ]);
               /* ========================== save or update in split_payment_stored_card table  END ============== */
            } catch (\Exception $e) {
                $this->helper->log($method, (string)$e);

                return $result->setData(['success' => 0,'msg'=>__($e->getMessage())]);
            }
        } else {
            return $result->setData(['success' => 0,'msg'=>__('Invalid Request.')]);
        }
    }
}
