<?php
namespace Dcw\RequestQuote\Controller\Quote;

use Amasty\RequestQuote\Controller\Quote\RemoveFromMinicart as RemoveFromMinicartVendor;
use Magento\Checkout\Model\Sidebar;
use Magento\Framework\View\Element\Template;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Pricing\Adjustment\Calculator;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Data\Form\FormKey\Validator;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\Quote\Item as QuoteItem;

class RemoveFromMinicart extends RemoveFromMinicartVendor
{
    private const ITEM_ID_PARAM = 'item_id';
    /**
     * @var Sidebar
     */
    protected $sidebar;

    protected $request;

    protected $resultFactory;

    protected $cart;

    protected $formKeyValidator;

    protected $resultJsonFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Sidebar $sidebar,
        Template\Context $context,
        RequestInterface $request,
        ResultFactory $resultFactory,
        Cart $cart,
        Validator $formKeyValidator,
        LoggerInterface $logger,
        JsonFactory $resultJsonFactory,
        array $data = []
    ) {
        $this->context = $context;
        $this->sidebar = $sidebar;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->cart = $cart;
        $this->formKeyValidator = $formKeyValidator;
        $this->logger = $logger;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct( $request, $resultFactory, $cart, $formKeyValidator, $logger);
        //parent::__construct($context, $resultFactory);
    }

    public function execute()
    {
        /** @var ResultJson $resultJson */
        //$resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson = $this->resultJsonFactory->create();
        if (!$this->formKeyValidator->validate($this->request)) {
            $resultJson->setData([
                'success' => false,
                'error_message' => __('We can\'t remove the quote.')
            ]);
            return $resultJson;
        }

        if ($itemId = $this->getItemId()) {
            $quote = $this->cart->getQuote();
            if (($quoteItem = $quote->getItemById($itemId))
                && $this->isAmastyQuoteItem($quoteItem)
            ) {
                try {
                    foreach ($quote->getAllVisibleItems() as $quoteItem) {
                        if ($this->isAmastyQuoteItem($quoteItem)) {
                            $quoteItem->removeOption('amasty_quote_price');
                            $quote->deleteItem($quoteItem);
                        }
                    }

                    $quote->setTotalsCollectedFlag(false);
                    $this->cart->save();
                } catch (Exception $e) {
                    $error = __('We can\'t remove the quote.');
                    $this->logger->critical($e);
                }
            }else{
                $itemId = (int)$this->request->getParam('item_id');
        
                try {
                    $this->sidebar->checkQuoteItem($itemId);
                    $this->sidebar->removeQuoteItem($itemId);
                  
                } catch (LocalizedException $e) {
                    $error = $e->getMessage();
                } catch (\Zend_Db_Exception $e) {
                    $this->logger->critical($e);
                    $error = __('An unspecified error occurred. Please contact us for assistance.');
                } catch (Exception $e) {
                    $this->logger->critical($e);
                    $error = $e->getMessage();
                }
            }
        }

        if (isset($error)) {
            $resultData = [
                'success' => false,
                'error_message' => $error
            ];
        } else {
            $resultData['success'] = true;
        }
        $resultJson->setData($resultData);

        return $resultJson;
    }

    private function getItemId(): int
    {
        return (int) $this->request->getParam(self::ITEM_ID_PARAM);
    }

    private function isAmastyQuoteItem(QuoteItem $quoteItem): bool
    {
        return (bool) $quoteItem->getOptionByCode('amasty_quote_price');
    }
}