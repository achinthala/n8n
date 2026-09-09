<?php
namespace WeltPixel\GA4\Block\MetaPixel;

/**
 * Class \WeltPixel\GA4\Block\MetaPixel\InitiateCheckout
 */
class InitiateCheckout extends Common
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

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
        $quote = $this->checkoutSession->getQuote();
        $productIds = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $productIds[] = $this->helper->getMetaProductId($product);
        }

        return $this->arrayToCommaSeparatedString($productIds);
    }

    /**
     * @return int
     */
    public function getNumItems()
    {
        $quote = $this->checkoutSession->getQuote();
        $numItems = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            $numItems += $item->getQty();
        }

        return $numItems;
    }

    /**
     * @return false|string
     */
    public function getContents()
    {
        $quote = $this->checkoutSession->getQuote();
        $cartItems = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $cartItems[] = [
                'id' => $this->helper->getMetaProductId($item->getProduct()),
                'quantity' => $item->getQty(),
                'item_price' => floatval(number_format($item->getProduct()->getPriceInfo()->getPrice('final_price')->getValue() ?? 0, 2, '.', ''))
            ];
        }

        return json_encode($cartItems);
    }

    /**
     * @return float|int
     */
    public function getValue()
    {
        $quote = $this->checkoutSession->getQuote();
        $grandTotal = $quote->getGrandTotal() ?? 0;

        return $grandTotal;
    }
}
