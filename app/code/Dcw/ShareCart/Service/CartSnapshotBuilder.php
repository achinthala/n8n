<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Service;

use Magento\Quote\Api\Data\CartInterface;

class CartSnapshotBuilder
{
    public function buildFromQuote(CartInterface $quote): string
    {
        $items = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $buyRequest = $item->getBuyRequest();
            $buyRequestData = $buyRequest ? $buyRequest->getData() : ['qty' => (float) $item->getQty()];

            $customPrice = $item->getCustomPrice();
            if ($customPrice !== null && (float) $customPrice > 0) {
                $buyRequestData['custom_price'] = (float) $customPrice;
            }

            $items[] = [
                'product_id' => (int) $item->getProductId(),
                'sku' => (string) $item->getSku(),
                'qty' => (float) $item->getQty(),
                'product_type' => (string) $item->getProductType(),
                'buy_request' => $buyRequestData,
                'pdp_line_item' => (string) ($item->getData('pdp_line_item') ?? ''),
            ];
        }

        return (string) json_encode([
            'store_id' => (int) $quote->getStoreId(),
            'currency' => (string) $quote->getQuoteCurrencyCode(),
            'items' => $items,
        ]);
    }

    /**
     * @return array{store_id:int,currency:string,items:array<int,array<string,mixed>>}
     */
    public function decodeSnapshot(string $snapshot): array
    {
        $data = json_decode($snapshot, true);

        if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
            throw new \InvalidArgumentException(__('Invalid cart snapshot.'));
        }

        return $data;
    }
}
