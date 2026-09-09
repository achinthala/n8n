<?php
/**
 * Controller to load customer section data
 * Includes active cart icon based on session flag
 */
declare(strict_types=1);

namespace Dcw\RequestQuote\Controller\Section;

use Magento\Customer\Controller\Section\Load as CustomerLoad;
use Magento\Checkout\Model\Session as CheckoutSession;
use Dcw\RequestQuote\ViewModel\ActiveCartIcon;

class Load extends CustomerLoad
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Customer\CustomerData\Section\Identifier $sectionIdentifier
     * @param \Magento\Customer\CustomerData\SectionPoolInterface $sectionPool
     * @param \Magento\Framework\Escaper|null $escaper
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\CustomerData\Section\Identifier $sectionIdentifier,
        \Magento\Customer\CustomerData\SectionPoolInterface $sectionPool,
        \Magento\Framework\Escaper $escaper = null,
        CheckoutSession $checkoutSession = null
    ) {
        $this->checkoutSession = $checkoutSession ?? \Magento\Framework\App\ObjectManager::getInstance()
            ->get(CheckoutSession::class);
        parent::__construct($context, $resultJsonFactory, $sectionIdentifier, $sectionPool, $escaper);
    }

    public function execute()
    { 
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store', true);
        $resultJson->setHeader('Pragma', 'no-cache', true);
        try {
            $sectionNames = $this->getRequest()->getParam('sections');
            $sectionNames = $sectionNames ? array_unique(\explode(',', $sectionNames)) : null;

            $forceNewSectionTimestamp = $this->getRequest()->getParam('force_new_section_timestamp');
            if ('false' === $forceNewSectionTimestamp) {
                $forceNewSectionTimestamp = false;
            }
            
            $response = $this->sectionPool->getSectionsData($sectionNames, (bool)$forceNewSectionTimestamp);
            //$response['cart'] = $this->updateCartSubtotal($response);
            $activeIcon = $this->checkoutSession->getData(ActiveCartIcon::getSessionKey()) ?: 'cart';
            // When session says 'quote' but quote has no items, return 'cart' so the default cart icon always shows
            if ($activeIcon === ActiveCartIcon::getTypeQuote() && isset($response['quotecart']['data'])) {
                $summaryCount = (int)($response['quotecart']['data']['summary_count'] ?? 0);
                if ($summaryCount <= 0) {
                    $activeIcon = ActiveCartIcon::getTypeCart();
                }
            }
            // When session says 'cart' (e.g. after Checkout Later), force empty quotecart - Amasty may auto-reload
            // quote via getActiveForCustomer when navigating to category/other pages
            if ($activeIcon === ActiveCartIcon::getTypeCart() && isset($response['quotecart']['data'])) {
                $response['quotecart']['data'] = array_merge($response['quotecart']['data'], [
                    'items' => [],
                    'summary_count' => 0,
                ]);
            }
            $response['active_cart_icon'] = $activeIcon;
        } catch (\Exception $e) {
            $resultJson->setStatusHeader(
                \Laminas\Http\Response::STATUS_CODE_400,
                \Laminas\Http\AbstractMessage::VERSION_11,
                'Bad Request'
            );
            $response = ['message' => $e->getMessage()];
        }

        return $resultJson->setData($response);
    }

    public function updateCartSubtotal($cart) {
        if (!isset($cart['cart']) || !is_array($cart['cart'])) {
            // Handle the case where 'cart' is not defined or not an array
            $calculatedSubtotal = 0;
            $cart['cart']['subtotalAmount'] = number_format($calculatedSubtotal, 4, '.', '');
            $cart['cart']['subtotal'] ='<span class="price">$' . number_format($calculatedSubtotal, 2, '.', '') . '</span>';
            $cart['cart']['summary_count']=0;

            return $cart;
        }
        $cart = $cart['cart'];
        // Check if subtotalAmount is 0
        if (isset($cart['summary_count']) && $cart['summary_count'] > 0 && floatval($cart['subtotalAmount']) === 0.0) {
            $calculatedSubtotal = 0;
    
            // Calculate the subtotal from the product prices
            foreach ($cart['items'] as $item) {
                $calculatedSubtotal += floatval($item['product_price_value'] ?? 0) * intval($item['qty'] ?? 1);
            }
    
            // Update the subtotalAmount and subtotal
            $cart['subtotalAmount'] = number_format($calculatedSubtotal, 4, '.', '');
            $cart['subtotal'] = '<span class="price">$' . number_format($calculatedSubtotal, 2, '.', '') . '</span>';
        }
    
        return $cart;
    }
}
