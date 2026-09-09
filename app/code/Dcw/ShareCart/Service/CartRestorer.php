<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Service;

use Dcw\AdvanceSearch\ViewModel\Data as AdvanceSearchData;
use Dcw\ShareCart\Api\Data\ShareCartInterface;
use Dcw\ShareCart\Model\Config;
use Dcw\ShareCart\Model\ShareCart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Restores shared cart items via cart->addProduct() + checkout_cart_product_add_after observer.
 * Roll lines of the same configurable are kept separate via additional_options before add.
 */
class CartRestorer
{
    public function __construct(
        private readonly CartSnapshotBuilder $snapshotBuilder,
        private readonly Cart $cart,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly AdvanceSearchData $advanceSearchData,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly SerializerInterface $serializer,
        private readonly HttpRequest $request,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function restore(ShareCart $shareCart): void
    {
        $shareId = $shareCart->getShareId();

        try {
            $snapshot = $this->snapshotBuilder->decodeSnapshot((string) $shareCart->getData('cart_snapshot'));
        } catch (\InvalidArgumentException) {
            throw new LocalizedException(__('Invalid cart snapshot.'));
        }

        $storeId = (int) ($snapshot['store_id'] ?? $shareCart->getData('store_id'));
        $items = is_array($snapshot['items'] ?? null) ? $snapshot['items'] : [];
        $sharedItemCount = count($items);

        if ($storeId > 0) {
            $this->storeManager->setCurrentStore($storeId);
        }

        $mode = $this->config->getRecipientCartMode($storeId);
        $quote = $this->cart->getQuote();

        if (!$quote->getId()) {
            $quote->setIsActive(true);
            $quote->save();
        }

        if ($mode === ShareCartInterface::RECIPIENT_CART_MODE_REPLACE) {
            foreach ($quote->getAllVisibleItems() as $item) {
                $quote->removeItem($item->getId());
            }

            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();
            $quote->save();
        }

        $addedCount = 0;
        $mergedCount = 0;
        $skippedCount = 0;

        foreach ($items as $index => $itemData) {
            if (!is_array($itemData)) {
                $skippedCount++;
                continue;
            }

            try {
                $result = $this->addItemToCart($itemData, $storeId, $mode);

                if ($result === 'merged') {
                    $mergedCount++;
                } elseif ($result === 'added') {
                    $addedCount++;
                } else {
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                $skippedCount++;
                $this->logger->warning('ShareCart item restore skipped', [
                    'share_id' => $shareId,
                    'item_index' => $index,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $quote = $this->cart->getQuote();
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $quote->save();

        $this->reconcileAllRestoredItems($items, $storeId);

        $quote = $this->cart->getQuote();
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $quote->save();
        $this->cart->save();

        $finalItemCount = count($quote->getAllVisibleItems());

        $this->logger->info('ShareCart items restored', [
            'share_id' => $shareId,
            'mode' => $mode,
            'items_added' => $addedCount,
            'items_merged' => $mergedCount,
            'items_skipped' => $skippedCount,
        ]);

        if ($finalItemCount === 0 && $sharedItemCount > 0 && $addedCount === 0 && $mergedCount === 0) {
            throw new LocalizedException(__('Unable to add the shared cart items to your cart.'));
        }
    }

    /**
     * @param array<string, mixed> $itemData
     * @throws LocalizedException
     */
    private function addItemToCart(array $itemData, int $storeId, string $mode): string
    {
        $productId = (int) ($itemData['product_id'] ?? 0);
        if ($productId <= 0) {
            return 'skipped';
        }

        $buyRequestData = $this->prepareBuyRequestData($itemData);

        if ($mode === ShareCartInterface::RECIPIENT_CART_MODE_MERGE) {
            $existingItem = $this->findMatchingQuoteItem($productId, $buyRequestData);
            if ($existingItem !== null) {
                $existingItem->setQty($existingItem->getQty() + (float) $buyRequestData['qty']);
                $this->finalizeRestoredQuoteItem($existingItem, $itemData, $buyRequestData, $storeId);
                $this->cart->getQuote()->setTotalsCollectedFlag(false);
                $this->cart->save();

                return 'merged';
            }
        }

        $visibleItemIdsBeforeAdd = $this->collectVisibleQuoteItemIds();

        /** @var Product $product */
        $product = $this->productRepository->getById($productId, false, $storeId);
        $product = $this->prepareProductForAdd($product, $buyRequestData);
        $this->setRequestPostFromBuyRequest($buyRequestData);

        $this->cart->addProduct($product, new DataObject($buyRequestData));
        $this->cart->save();

        $quoteItem = $this->findQuoteItemForRestore($productId, $buyRequestData, $visibleItemIdsBeforeAdd);
        if ($quoteItem !== null) {
            $this->finalizeRestoredQuoteItem($quoteItem, $itemData, $buyRequestData, $storeId);
        } else {
            $this->logger->warning('ShareCart restore could not locate added quote item', [
                'product_id' => $productId,
                'roll_type' => $buyRequestData['RollType'] ?? null,
            ]);
        }

        $this->cart->getQuote()->setTotalsCollectedFlag(false);
        $this->cart->save();

        return 'added';
    }

    /**
     * @param array<string, mixed> $itemData
     * @return array<string, mixed>
     */
    private function prepareBuyRequestData(array $itemData): array
    {
        $buyRequestData = is_array($itemData['buy_request'] ?? null) ? $itemData['buy_request'] : [];
        if (!isset($buyRequestData['qty'])) {
            $buyRequestData['qty'] = (float) ($itemData['qty'] ?? 1);
        }

        $buyRequestData = $this->enrichBuyRequestFromSnapshot($itemData, $buyRequestData);
        unset($buyRequestData['custom_price']);

        return $buyRequestData;
    }

    /**
     * @param array<string, mixed> $itemData
     * @param array<string, mixed> $buyRequestData
     */
    private function finalizeRestoredQuoteItem(
        QuoteItem $quoteItem,
        array $itemData,
        array $buyRequestData,
        int $storeId
    ): void {
        $priceTargetItem = $quoteItem->getParentItem() ?: $quoteItem;
        $qty = (float) ($buyRequestData['qty'] ?? $quoteItem->getQty());

        $unitPrice = $this->resolveRecipientUnitPrice($quoteItem, $buyRequestData, $storeId);
        if ($unitPrice === null || $unitPrice <= 0) {
            $unitPrice = $this->readQuoteItemUnitPrice($quoteItem);
        }
        if ($unitPrice <= 0) {
            $snapshotPrice = $this->resolveSnapshotUnitPrice($itemData);
            if ($snapshotPrice !== null && $snapshotPrice > 0) {
                $unitPrice = $snapshotPrice;
            }
        }

        if ($unitPrice > 0) {
            $this->applyItemUnitPrice($priceTargetItem, $unitPrice);

            $existingBuyRequest = $quoteItem->getBuyRequest();
            $requestData = $existingBuyRequest ? $existingBuyRequest->getData() : [];
            $requestData = array_merge($requestData, $buyRequestData);
            $requestData['custom_price'] = $unitPrice;

            $quoteItem->addOption([
                'product_id' => $quoteItem->getProductId(),
                'code' => 'info_buyRequest',
                'value' => $this->serializer->serialize($requestData),
            ]);
        } else {
            $this->logger->warning('ShareCart restore item has no price after recipient or snapshot fallback', [
                'product_id' => $quoteItem->getProductId(),
                'sku' => $itemData['sku'] ?? null,
                'roll_type' => $buyRequestData['RollType'] ?? null,
            ]);
        }

        $pdpJson = $this->buildPdpLineItemJson($itemData, $buyRequestData, $unitPrice, $qty);
        if ($pdpJson === '') {
            return;
        }

        $quoteItem->setPdpLineItem($pdpJson);
        if ($quoteItem->getProductType() === 'configurable') {
            foreach ($quoteItem->getChildren() as $childItem) {
                $childItem->setPdpLineItem($pdpJson);
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $snapshotItems
     */
    private function reconcileAllRestoredItems(array $snapshotItems, int $storeId): void
    {
        $usedQuoteItemIds = [];

        foreach ($snapshotItems as $itemData) {
            if (!is_array($itemData)) {
                continue;
            }

            $productId = (int) ($itemData['product_id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $buyRequestData = $this->prepareBuyRequestData($itemData);
            $quoteItem = $this->findUnusedQuoteItemForSnapshotLine(
                $productId,
                $buyRequestData,
                (string) ($itemData['sku'] ?? ''),
                $usedQuoteItemIds
            );

            if ($quoteItem === null) {
                continue;
            }

            $itemId = (int) $quoteItem->getId();
            if ($itemId > 0) {
                $usedQuoteItemIds[$itemId] = true;
            }

            $this->finalizeRestoredQuoteItem($quoteItem, $itemData, $buyRequestData, $storeId);
        }
    }

    /**
     * @param array<string, mixed> $buyRequestData
     */
    private function resolveRecipientUnitPrice(QuoteItem $quoteItem, array $buyRequestData, int $storeId): ?float
    {
        $childProductId = $this->resolveChildProductId($quoteItem, $buyRequestData, $storeId);
        if ($childProductId === null) {
            return null;
        }

        $rollType = (string) ($buyRequestData['RollType'] ?? '');
        $roomWidth = (float) ($buyRequestData['RoomWidth'] ?? 0);
        if ($roomWidth > 0 && $rollType !== '') {
            $length = $rollType === 'Custom Roll Length'
                ? (float) ($buyRequestData['custom_length'] ?? 0)
                : (float) ($buyRequestData['RoomLength'] ?? 0);

            if ($length > 0) {
                $calculated = $this->advanceSearchData->getCalculatedPrice($childProductId);
                if (isset($calculated['price']) && (float) $calculated['price'] > 0) {
                    $rollUnitPrice = $roomWidth * $length * (float) $calculated['price'];
                    if ($rollUnitPrice > 0) {
                        return $rollUnitPrice;
                    }
                }
            }
        }

        try {
            $childProduct = $this->productRepository->getById($childProductId);
            $finalPrice = (float) $childProduct->getFinalPrice(1);
            if ($finalPrice > 0) {
                return $finalPrice;
            }
        } catch (\Exception) {
            // Fall through.
        }

        $calculated = $this->advanceSearchData->getCalculatedPrice($childProductId);
        if (!isset($calculated['price']) || (float) $calculated['price'] <= 0) {
            return null;
        }

        $calType = strtolower((string) ($calculated['calType'] ?? ''));
        if (in_array($calType, ['each', 'none'], true)) {
            return (float) $calculated['price'];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $buyRequestData
     */
    private function resolveChildProductId(QuoteItem $quoteItem, array $buyRequestData, int $storeId): ?int
    {
        if (isset($buyRequestData['selected_option_child_id']) && (int) $buyRequestData['selected_option_child_id'] > 0) {
            return (int) $buyRequestData['selected_option_child_id'];
        }

        $productId = (int) $quoteItem->getProductId();
        $childFromSuper = $this->resolveChildProductIdFromSuperAttribute($productId, $buyRequestData, $storeId);
        if ($childFromSuper !== null) {
            return $childFromSuper;
        }

        $sku = (string) $quoteItem->getSku();
        if ($sku !== '') {
            try {
                $childProduct = $this->productRepository->get($sku);
                if ($childProduct->getId()) {
                    return (int) $childProduct->getId();
                }
            } catch (\Exception) {
                // Fall through.
            }
        }

        foreach ($quoteItem->getChildren() as $childItem) {
            $childProductId = (int) $childItem->getProductId();
            if ($childProductId > 0) {
                return $childProductId;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $buyRequestData
     */
    private function resolveChildProductIdFromSuperAttribute(
        int $configurableProductId,
        array $buyRequestData,
        int $storeId
    ): ?int {
        $superAttribute = $buyRequestData['super_attribute'] ?? null;
        if (!is_array($superAttribute) || $superAttribute === []) {
            return null;
        }

        try {
            $product = $this->productRepository->getById($configurableProductId, false, $storeId);
            $typeInstance = $product->getTypeInstance();
            if (!$typeInstance instanceof ConfigurableType) {
                return null;
            }

            $simpleProduct = $typeInstance->getProductByAttributes($superAttribute, $product);
            if ($simpleProduct && $simpleProduct->getId()) {
                return (int) $simpleProduct->getId();
            }
        } catch (\Exception) {
            return null;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $itemData
     */
    private function resolveSnapshotUnitPrice(array $itemData): ?float
    {
        $pdpLineItem = $this->decodePdpLineItem($itemData);
        if (isset($pdpLineItem['UnitPrice'])) {
            $parsed = $this->parseNumericPrice($pdpLineItem['UnitPrice']);
            if ($parsed !== null && $parsed > 0) {
                return $parsed;
            }
        }

        $snapshotBuyRequest = is_array($itemData['buy_request'] ?? null) ? $itemData['buy_request'] : [];
        if (array_key_exists('custom_price', $snapshotBuyRequest)) {
            $parsed = $this->parseNumericPrice($snapshotBuyRequest['custom_price']);
            if ($parsed !== null && $parsed > 0) {
                return $parsed;
            }
        }

        if (isset($pdpLineItem['LineItemPrice'], $pdpLineItem['LineItemUnitQuantity'])) {
            $lineQty = (float) $pdpLineItem['LineItemUnitQuantity'];
            $linePrice = $this->parseNumericPrice($pdpLineItem['LineItemPrice']);
            if ($lineQty > 0 && $linePrice !== null && $linePrice > 0) {
                return $linePrice / $lineQty;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $itemData
     * @param array<string, mixed> $buyRequestData
     */
    private function buildPdpLineItemJson(array $itemData, array $buyRequestData, float $unitPrice, float $qty): string
    {
        $pdpLineItem = $this->decodePdpLineItem($itemData);

        if ($unitPrice > 0) {
            $pdpLineItem['UnitPrice'] = $unitPrice;
            $pdpLineItem['LineItemPrice'] = $unitPrice * $qty;
        }

        $pdpLineItem['LineItemUnitQuantity'] = $qty;

        $shipToText = trim((string) ($buyRequestData['ship_to_text'] ?? ''));
        if ($shipToText === '' && isset($pdpLineItem['shipping_estimate'])) {
            $shipToText = trim((string) $pdpLineItem['shipping_estimate']);
        }
        if ($shipToText !== '') {
            $pdpLineItem['shipping_estimate'] = $shipToText;
        }

        foreach ($this->getPdpFieldMap() as $pdpKey => $postKey) {
            if (in_array($postKey, ['custom_price', 'qty'], true)) {
                continue;
            }
            if (!array_key_exists($postKey, $buyRequestData)) {
                continue;
            }
            $value = $buyRequestData[$postKey];
            if ($value === null || $value === '') {
                continue;
            }
            $pdpLineItem[$pdpKey] = $value;
        }

        if ($pdpLineItem === [] && $unitPrice <= 0 && $shipToText === '') {
            return '';
        }

        return (string) json_encode($pdpLineItem);
    }

    private function applyItemUnitPrice(QuoteItem $priceTargetItem, float $unitPrice): void
    {
        $priceTargetItem->setCustomPrice($unitPrice);
        $priceTargetItem->setOriginalCustomPrice($unitPrice);
        $priceTargetItem->setPrice($unitPrice);
        $priceTargetItem->setBasePrice($unitPrice);
        $priceTargetItem->setBaseCalculationPrice($unitPrice);
        $priceTargetItem->unsetData('calculation_price');
        $priceTargetItem->unsetData('base_calculation_price');
        $priceTargetItem->getProduct()->setIsSuperMode(true);
        $priceTargetItem->calcRowTotal();
    }

    private function readQuoteItemUnitPrice(QuoteItem $quoteItem): float
    {
        $targetItem = $quoteItem->getParentItem() ?: $quoteItem;
        $unitPrice = (float) $targetItem->getCustomPrice();
        if ($unitPrice > 0) {
            return $unitPrice;
        }

        foreach ($quoteItem->getChildren() as $childItem) {
            $childPrice = (float) $childItem->getCustomPrice();
            if ($childPrice > 0) {
                return $childPrice;
            }
        }

        return 0.0;
    }

    /**
     * @return array<int, true>
     */
    private function collectVisibleQuoteItemIds(): array
    {
        $ids = [];
        foreach ($this->cart->getQuote()->getAllVisibleItems() as $item) {
            $itemId = (int) $item->getId();
            if ($itemId > 0) {
                $ids[$itemId] = true;
            }
        }

        return $ids;
    }

    /**
     * @param array<string, mixed> $buyRequestData
     * @param array<int, true> $visibleItemIdsBeforeAdd
     */
    private function findQuoteItemForRestore(
        int $productId,
        array $buyRequestData,
        array $visibleItemIdsBeforeAdd
    ): ?QuoteItem {
        $matched = $this->findMatchingQuoteItem($productId, $buyRequestData);
        if ($matched !== null) {
            return $matched;
        }

        $newItems = [];
        foreach ($this->cart->getQuote()->getAllVisibleItems() as $item) {
            if ((int) $item->getProductId() !== $productId) {
                continue;
            }

            $itemId = (int) $item->getId();
            if ($itemId > 0 && !isset($visibleItemIdsBeforeAdd[$itemId])) {
                $newItems[] = $item;
            }
        }

        if (count($newItems) === 1) {
            return $newItems[0];
        }

        if ($newItems !== []) {
            $targetSignature = $this->normalizeAdditionalOptionsSignature(
                $this->buildAdditionalOptions($buyRequestData)
            );

            foreach ($newItems as $item) {
                if ($this->getQuoteItemOptionsSignature($item) === $targetSignature) {
                    return $item;
                }
            }

            return $newItems[count($newItems) - 1];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $buyRequestData
     * @param array<int, true> $usedQuoteItemIds
     */
    private function findUnusedQuoteItemForSnapshotLine(
        int $productId,
        array $buyRequestData,
        string $snapshotSku,
        array $usedQuoteItemIds
    ): ?QuoteItem {
        $targetSignature = $this->normalizeAdditionalOptionsSignature(
            $this->buildAdditionalOptions($buyRequestData)
        );

        $candidates = [];
        foreach ($this->cart->getQuote()->getAllVisibleItems() as $item) {
            $itemId = (int) $item->getId();
            if ($itemId > 0 && isset($usedQuoteItemIds[$itemId])) {
                continue;
            }
            if ((int) $item->getProductId() !== $productId) {
                continue;
            }
            $candidates[] = $item;
        }

        foreach ($candidates as $item) {
            if ($this->getQuoteItemOptionsSignature($item) === $targetSignature) {
                return $item;
            }
        }

        if ($snapshotSku !== '') {
            foreach ($candidates as $item) {
                if ((string) $item->getSku() === $snapshotSku) {
                    return $item;
                }
            }
        }

        return $candidates[0] ?? null;
    }

    /**
     * @param array<string, mixed> $buyRequestData
     */
    private function findMatchingQuoteItem(int $productId, array $buyRequestData): ?QuoteItem
    {
        $targetSignature = $this->normalizeAdditionalOptionsSignature(
            $this->buildAdditionalOptions($buyRequestData)
        );

        foreach ($this->cart->getQuote()->getAllVisibleItems() as $item) {
            if ((int) $item->getProductId() !== $productId) {
                continue;
            }

            if ($this->getQuoteItemOptionsSignature($item) === $targetSignature) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $itemData
     * @param array<string, mixed> $buyRequestData
     * @return array<string, mixed>
     */
    private function enrichBuyRequestFromSnapshot(array $itemData, array $buyRequestData): array
    {
        $pdpLineItem = $this->decodePdpLineItem($itemData);
        if ($pdpLineItem === []) {
            return $buyRequestData;
        }

        foreach ($this->getPdpFieldMap() as $pdpKey => $postKey) {
            if ($postKey === 'custom_price') {
                continue;
            }
            if (!array_key_exists($pdpKey, $pdpLineItem)) {
                continue;
            }
            $value = $pdpLineItem[$pdpKey];
            if ($value === null || $value === '') {
                continue;
            }
            $buyRequestData[$postKey] = $value;
        }

        return $buyRequestData;
    }

    /**
     * @param array<string, mixed> $itemData
     * @return array<string, mixed>
     */
    private function decodePdpLineItem(array $itemData): array
    {
        $pdpJson = trim((string) ($itemData['pdp_line_item'] ?? ''));
        if ($pdpJson === '') {
            return [];
        }

        $pdpLineItem = json_decode($pdpJson, true);

        return is_array($pdpLineItem) ? $pdpLineItem : [];
    }

    /**
     * @return array<string, string>
     */
    private function getPdpFieldMap(): array
    {
        return [
            'LineItemUnitQuantity' => 'qty',
            'UnitPrice' => 'custom_price',
            'RoomWidth' => 'RoomWidth',
            'RoomLength' => 'RoomLength',
            'RollType' => 'RollType',
            'RollSize' => 'RollSize',
            'shipping_estimate' => 'ship_to_text',
            'CustomLength' => 'custom_length',
            'Length' => 'length',
            'Width' => 'width',
            'PricePerLinearFoot' => 'PricePerLinearFoot',
            'CustomerRoomWidth' => 'CustomerRoomWidth',
            'CustomerRoomLength' => 'CustomerRoomLength',
            'CustomerRoomLengthGroup' => 'CustomerRoomLengthGroup',
        ];
    }

    /**
     * @param array<string, mixed> $buyRequestData
     */
    private function prepareProductForAdd(Product $product, array $buyRequestData): Product
    {
        $product = clone $product;
        $additionalOptions = $this->buildAdditionalOptions($buyRequestData);

        if ($additionalOptions !== []) {
            $product->addCustomOption(
                'additional_options',
                $this->serializer->serialize($additionalOptions)
            );
        }

        return $product;
    }

    /**
     * @param array<string, mixed> $buyRequestData
     */
    private function setRequestPostFromBuyRequest(array $buyRequestData): void
    {
        $post = $buyRequestData;
        unset($post['custom_price']);

        if (!isset($post['pricePerSqft']) && isset($post['PricePerSqft_'])) {
            $post['pricePerSqft'] = $post['PricePerSqft_'];
        }

        $this->request->setPostValue($post);
    }

    /**
     * @param array<string, mixed> $buyRequestData
     * @return array<string, array{label: string, value: string}>
     */
    private function buildAdditionalOptions(array $buyRequestData): array
    {
        $rollType = (string) ($buyRequestData['RollType'] ?? '');

        if (isset($buyRequestData['RoomWidth'], $buyRequestData['RoomLength'])
            && (float) $buyRequestData['RoomWidth'] > 0
            && (float) $buyRequestData['RoomLength'] > 0
            && $rollType === 'Recommended Roll Length'
        ) {
            return [
                'room_width' => [
                    'label' => 'Roll Width',
                    'value' => (string) $buyRequestData['RoomWidth'],
                ],
                'room_length' => [
                    'label' => 'Roll Length',
                    'value' => (string) $buyRequestData['RoomLength'],
                ],
            ];
        }

        if (isset($buyRequestData['custom_length'])
            && (float) $buyRequestData['custom_length'] > 0
            && $rollType === 'Custom Roll Length'
        ) {
            return [
                'custom_length' => [
                    'label' => 'Custom Length',
                    'value' => (string) $buyRequestData['custom_length'],
                ],
            ];
        }

        return [];
    }

    private function getQuoteItemOptionsSignature(QuoteItem $item): string
    {
        $existingOption = $item->getOptionByCode('additional_options');

        return $this->normalizeAdditionalOptionsSignature(
            $existingOption ? (string) $existingOption->getValue() : ''
        );
    }

    /**
     * @param array<string, array{label: string, value: mixed}>|string $additionalOptions
     */
    private function normalizeAdditionalOptionsSignature(array|string $additionalOptions): string
    {
        if (is_string($additionalOptions)) {
            if ($additionalOptions === '') {
                return '';
            }

            try {
                $additionalOptions = $this->serializer->unserialize($additionalOptions);
            } catch (\InvalidArgumentException) {
                return $additionalOptions;
            }
        }

        if ($additionalOptions === []) {
            return '';
        }

        foreach ($additionalOptions as $key => $option) {
            if (is_array($option) && array_key_exists('value', $option)) {
                $additionalOptions[$key]['value'] = (string) $option['value'];
            }
        }

        ksort($additionalOptions);

        return md5((string) json_encode($additionalOptions));
    }

    private function parseNumericPrice(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (preg_match('/(\d+(?:\.\d+)?)/', (string) $value, $matches) === 1) {
            return (float) $matches[1];
        }

        return null;
    }
}
