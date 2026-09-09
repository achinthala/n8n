<?php
namespace WeltPixel\GA4\Block\MetaPixel;

/**
 * Class \WeltPixel\GA4\Block\MetaPixel\Purchase
 */
class Purchase extends Common
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \WeltPixel\GA4\Helper\MetaPixelTracking $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \WeltPixel\GA4\Helper\MetaPixelTracking $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    )
    {
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $helper, $data);
    }

    /**
     * @return string
     */
    public function getContentIds()
    {
        if (!$this->order) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }
        $productIds = [];

        if ($this->order) {
            foreach ($this->order->getAllVisibleItems() as $item) {
                $product = $item->getProduct();
                $productIds[] = $this->helper->getMetaProductId($product);
            }
        }

        return $this->arrayToCommaSeparatedString($productIds);
    }

    /**
     * @return false|string
     */
    public function getContents()
    {
        if (!$this->order) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }
        $orderItems = [];
        if ($this->order) {
            foreach ($this->order->getAllVisibleItems() as $item) {
                $orderItems[] = [
                    'id' => $this->helper->getMetaProductId($item->getProduct()),
                    'quantity' => (int)$item->getQtyOrdered(),
                    'item_price' => floatval(number_format($item->getProduct()->getPriceInfo()->getPrice('final_price')->getValue() ?? 0, 2, '.', ''))
                ];
            }
        }

        return json_encode($orderItems);
    }

    /**
     * @return float|int
     */
    public function getValue()
    {
        if (!$this->order) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }
        return $this->order->getGrandtotal() ?? 0;
    }

    /**
     * @return string
     */
    public function getPaymentMethodTitle()
    {
        if (!$this->order) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }

        try {
            $payment = $this->order->getPayment();
            $method = $payment->getMethodInstance();
            $methodTitle = $method->getTitle();
        }
        catch (\Exception $e) {
            $methodTitle = '';
        }

        return $methodTitle;
    }
}
