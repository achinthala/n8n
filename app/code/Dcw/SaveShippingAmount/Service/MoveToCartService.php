<?php

declare(strict_types=1);

namespace Dcw\SaveShippingAmount\Service;

use Amasty\RequestQuote\Api\QuoteRepositoryInterface as AmastyQuoteRepository;
use Amasty\RequestQuote\Model\Quote\Frontend\GetAmastyQuote;
use Amasty\RequestQuote\Model\Quote\Move\MergeQuotes;
use Amasty\RequestQuote\Model\Source\Status;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface as MagentoQuoteRepository;
use Dcw\FlooringCalculation\ViewModel\Data as FlooringCalculationViewModel;
use Dcw\RequestQuote\Service\QuoteLockService;
use Dcw\RequestQuote\ViewModel\ActiveCartIcon;
use Dcw\RequestQuote\ViewModel\Data as RequestQuoteViewModel;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\Quote\Item\OptionFactory;

/**
 * Service to move an approved Amasty quote to the checkout cart.
 * Shared logic used by InCart controller and UpdatePost (Save Cart & Proceed to Checkout).
 */
class MoveToCartService
{
    public function __construct(
        private readonly Cart $cart,
        private readonly CheckoutSession $checkoutSession,
        private readonly GetAmastyQuote $getAmastyQuote,
        private readonly MergeQuotes $mergeQuotes,
        private readonly AmastyQuoteRepository $amastyQuoteRepository,
        private readonly MagentoQuoteRepository $magentoQuoteRepository,
        private readonly ResourceConnection $resource,
        private readonly FlooringCalculationViewModel $flooringCalculationViewModel,
        private readonly QuoteLockService $quoteLockService,
        private readonly SessionManagerInterface $sessionManager,
        private readonly RequestQuoteViewModel $requestQuoteViewModel,
        private readonly OptionFactory $optionFactory
    ) {
    }

    /**
     * Move approved quote to cart. Throws LocalizedException on failure.
     *
     * @param int $quoteId
     * @param bool $redirectToCheckout Set active cart icon when redirecting to checkout
     * @throws LocalizedException
     */
    public function execute(int $quoteId, bool $redirectToCheckout = false): void
    {
        $sessionId = $this->sessionManager->getSessionId();
        $lockStatus = $this->quoteLockService->getLockStatus($quoteId, $sessionId);

        if ($lockStatus && $lockStatus['is_locked'] && isset($lockStatus['locked_by_type']) && $lockStatus['locked_by_type'] === 'admin') {
            $adminName = $lockStatus['locked_by_name'] ?? 'Admin';
            throw new LocalizedException(
                __('This quote is currently being edited by %1. Please try again later.', $adminName)
            );
        }

        $this->checkStockForQuoteItems($quoteId);

        $currentQuote = $this->checkoutSession->getQuote();
        $amastyQuote = $this->getAmastyQuote->execute($currentQuote);
        if ($amastyQuote !== null) {
            // Cart has Amasty quote items - clear cart so we can merge the quote we're moving
            $this->cart->truncate()->save();
            $currentQuote = $this->checkoutSession->getQuote();
        }

        $this->copyAdminAssistanceToCurrentQuote($quoteId, $currentQuote);

        $approvedQuote = $this->amastyQuoteRepository->get($quoteId);
        if ($approvedQuote->getStatus() != Status::APPROVED) {
            throw new LocalizedException(__('Quote with ID %1 not approved', $quoteId));
        }

        try {
            $this->requestQuoteViewModel->recalculateQuotePrices($approvedQuote, true);
            $approvedQuote = $this->amastyQuoteRepository->get($quoteId);
        } catch (\Exception $e) {
            // Continue - prices will be recalculated in cart if needed
        }

        $this->mergeQuotes->execute($currentQuote, $approvedQuote);
        $this->magentoQuoteRepository->save($currentQuote);

        $quote = $this->magentoQuoteRepository->get($currentQuote->getId());
        $itemsUpdated = false;

        foreach ($quote->getAllItems() as $item) {
            if ($item->getParentItemId()) {
                continue;
            }

            if ($item->getNoDiscount()) {
                $item->setNoDiscount(false);
                $itemsUpdated = true;
            }

            $amastyQuoteIdOption = $item->getOptionByCode('amasty_quote_id');
            if (!$amastyQuoteIdOption) {
                $option = $this->optionFactory->create();
                $option->setItem($item);
                $option->setCode('amasty_quote_id');
                $option->setValue((string)$quoteId);
                $item->addOption($option);
                $itemsUpdated = true;
            }
        }

        if ($itemsUpdated) {
            $this->magentoQuoteRepository->save($quote);
            $quote = $this->magentoQuoteRepository->get($quote->getId());
        }

        $quote->collectTotals();
        $this->magentoQuoteRepository->save($quote);

        $this->setAmastyQuoteShippingAmount($quoteId);

        // Always show cart icon after moving to cart (items are now in cart)
        $this->checkoutSession->setData(ActiveCartIcon::getSessionKey(), ActiveCartIcon::getTypeCart());
    }

    private function copyAdminAssistanceToCurrentQuote(int $quoteId, \Magento\Quote\Model\Quote $currentQuote): void
    {
        $approvedQuote = $this->amastyQuoteRepository->get($quoteId);
        $currentQuote->setAdminUserAssistance($approvedQuote->getAdminUserAssistance());
    }

    private function setAmastyQuoteShippingAmount(int $quoteId): ?string
    {
        $amastyQuoteTable = $this->resource->getTableName('amasty_quote');
        $connection = $this->resource->getConnection();
        $sql = "SELECT shipping_amount FROM $amastyQuoteTable WHERE quote_id = :quoteId LIMIT 1";
        $result = $connection->fetchOne($sql, ['quoteId' => $quoteId]);

        if ($result !== null && $result > 0) {
            $this->checkoutSession->setAmastyQuoteShippingAmount($result);
        }
        return $result;
    }

    /**
     * @throws LocalizedException
     */
    private function checkStockForQuoteItems(int $quoteId): void
    {
        $loadQuoteById = $this->flooringCalculationViewModel->loadQuoteById($quoteId);

        if ($loadQuoteById === false || !is_iterable($loadQuoteById)) {
            throw new LocalizedException(__('Unable to load quote items.'));
        }

        foreach ($loadQuoteById as $item) {
            $itemSku = $item->getSku();
            $loadProduct = $this->flooringCalculationViewModel->loadProductBySku($itemSku);
            if ($loadProduct) {
                $itemQty = $item->getQty();
                $getPdpLineItem = $item->getPdpLineItem();
                if ($getPdpLineItem) {
                    $data = json_decode($getPdpLineItem, true);
                    $rollWidth = $data['RoomWidth'] ?? 0;
                    $rollLength = $data['RoomLength'] ?? 0;

                    if ($rollWidth > 0 && $rollLength > 0) {
                        $itemLineItemQty = $rollLength * $itemQty;
                        $getMiniCartQty = $this->flooringCalculationViewModel->getMiniCartQty($itemSku);
                        $totalRequestedQty = $getMiniCartQty + $itemLineItemQty;
                        $isProductCanAddToCart = $this->flooringCalculationViewModel->isProductCanAddToCart(
                            $loadProduct->getId(),
                            $totalRequestedQty
                        );
                        $availableQty = $this->flooringCalculationViewModel->getProductAvailableQty($loadProduct->getId());

                        if (!$isProductCanAddToCart) {
                            throw new LocalizedException(
                                __('You can\'t order more than %1 linear feet. Requested: %2 linear feet.', $availableQty, $totalRequestedQty)
                            );
                        }
                    }
                }
            }
        }
    }
}
