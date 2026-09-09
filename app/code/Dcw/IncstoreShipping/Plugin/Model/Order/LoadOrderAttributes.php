<?php

declare(strict_types=1);

namespace Dcw\IncstoreShipping\Plugin\Model\Order;

use Dcw\IncstoreShipping\Model\SplitPaymentAttribute;
use Dcw\SplitPayment\Model\ResourceModel\PaymentTransaction\CollectionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Sales\Api\Data\OrderItemExtensionInterfaceFactory;
use Magento\Framework\App\RequestInterface;

class LoadOrderAttributes
{
    protected $extensionFactory;
    protected $orderItemExtension;
    protected $orderItemExtensionFactory;
    protected $paymenttransaction;
    protected $splitpaymentAttribute;
	protected $request;
	
    public function __construct(
        OrderExtensionFactory $extensionFactory,
        OrderItemExtensionInterfaceFactory $orderItemExtension,
        OrderItemExtensionFactory $orderItemExtensionFactory,
		CollectionFactory $paymenttransaction,
        SplitPaymentAttribute $splitpaymentAttribute,
		RequestInterface $request
    ) {
        $this->extensionFactory = $extensionFactory;
        $this->orderItemExtension = $orderItemExtension;
        $this->orderItemExtensionFactory = $orderItemExtensionFactory;
		$this->paymenttransaction = $paymenttransaction;
        $this->splitpaymentAttribute = $splitpaymentAttribute;
		$this->request = $request;
    }

    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface $resultOrder
    ) {
        try {
            $this->applyOrderAttributes($resultOrder);
        } catch (\Throwable $e) {
            $this->logPluginError($resultOrder, 'afterGet', $e);
        }

        return $resultOrder;
    }

    public function afterGetList(
        OrderRepositoryInterface $subject,
        \Magento\Sales\Api\Data\OrderSearchResultInterface $orderSearchResult
    ) {
        foreach ($orderSearchResult->getItems() as $order) {
            try {
                $this->applyOrderAttributes($order);
            } catch (\Throwable $e) {
                $this->logPluginError($order, 'afterGetList', $e);
            }
        }

        return $orderSearchResult;
    }

    private function applyOrderAttributes(OrderInterface $order): void
    {
        try {
            $paymentInfo = $order->getPayment();

            if ($paymentInfo && $paymentInfo->getData('last_trans_id')) {
                $lastTransId = str_replace("-capture", "", $paymentInfo->getData('last_trans_id'));
                $paymentInfo->setData('last_trans_id', $lastTransId);
            }

            $this->AuthCodeTrim($paymentInfo);
        } catch (\Throwable $e) {
            $this->logPluginError($order, 'payment trim', $e);
        }

        $this->setSplitPaymentTransactionData($order);
        $this->setExtensionAttributes($order);
    }

    public function setSplitPaymentTransactionData(OrderInterface $order)
    {
        try {
            $extensionAttributes = $order->getExtensionAttributes();
            $orderExtension = $extensionAttributes ?: $this->extensionFactory->create();
            $paymenttrasactiondata = $this->paymenttransaction->create();
            $paymenttrasactiondata->addFieldToFilter('order_id', ['eq' => $order->getId()]);
            $paymentData = [];
            foreach ($paymenttrasactiondata as $payment) {
                $splitPayment = new SplitPaymentAttribute();
                $additionaldata = json_decode($payment['additional_information'] ?? '', true);
                if (!is_array($additionaldata)) {
                    $additionaldata = [];
                }
                $splitPayment->setResponseCode($additionaldata['response_code'] ?? '');
                $splitPayment->setAuthCode($additionaldata['auth_code'] ?? '');
                $splitPayment->setAvsResultCode($additionaldata['avs_result_code'] ?? '');
                $splitPayment->setCvvResultCode($payment['cc_cid_status'] ?? '');
                $splitPayment->setCavvResultCode($payment['cc_status'] ?? '');
                $splitPayment->setTransId($payment['trans_id'] ?? '');
                $splitPayment->setAccountNumber($additionaldata['acc_number'] ?? '');
                $splitPayment->setAccountType($additionaldata['card_type'] ?? '');
                $paymentData[] = $splitPayment;
            }
            $orderExtension->setData("split_payment", $paymentData);
            $order->setExtensionAttributes($orderExtension);
        } catch (\Throwable $e) {
            $this->logPluginError($order, 'setSplitPaymentTransactionData', $e);
        }
    }

    public function getMonths()
    {
        return [
            'Jan' => 1,
            'Feb' => 2,
            'Mar' => 3,
            'Apr' => 4,
            'May' => 5,
            'Jun' => 6,
            'Jul' => 7,
            'Aug' => 8,
            'Sep' => 9,
            'Oct' => 10,
            'Nov' => 11,
            'Dec' => 12,
        ];
    }

    public function AuthCodeTrim($paymentInfo)
    {
        // Auth code 32 Char NetSuite Maxlength fix
        if ($paymentInfo && is_array($paymentInfo->getData('additional_information'))) {
            $paymentAdditionalInfo = $paymentInfo->getData('additional_information');

            if (isset($paymentAdditionalInfo['amazon_session_id'])) {
                $authCode = $paymentAdditionalInfo['amazon_session_id'] ?? '';

                if (strlen($authCode) > 32) {
                    $lastHyphenPos = strrpos($authCode, '-');
                    if ($lastHyphenPos !== false) {
                        $authCode = substr($authCode, 0, $lastHyphenPos);
                    }
                }

                $paymentInfo->setAdditionalInformation('amazon_session_id', $authCode);
            }
        }
    }

    public function getESDMinMax($pdplineitemObject, $item, OrderInterface $order)
    {
        try {
            return $this->resolveESDMinMax($pdplineitemObject, $item, $order);
        } catch (\Throwable $e) {
            $this->logEsdParseError($order, $item, 'unexpected ESD parse error', [
                'exception' => $e->getMessage(),
                'shipping_estimate' => is_object($pdplineitemObject)
                    ? (string)($pdplineitemObject->shipping_estimate ?? '')
                    : '',
            ]);

            return ['esdMin' => '', 'esdMax' => ''];
        }
    }

    private function resolveESDMinMax($pdplineitemObject, $item, OrderInterface $order): array
    {
        $esdMinMax = ['esdMin' => '', 'esdMax' => ''];

        if ($pdplineitemObject === null) {
            return $esdMinMax;
        }

        $createdAtTimestamp = $this->parseTimestamp((string)$item->getCreatedAt());
        if ($createdAtTimestamp === null) {
            $this->logEsdParseError($order, $item, 'invalid item created_at', [
                'created_at' => (string)$item->getCreatedAt(),
                'shipping_estimate' => (string)($pdplineitemObject->shipping_estimate ?? ''),
            ]);
            return $esdMinMax;
        }

        if (!isset($pdplineitemObject->shipping_estimate)) {
            return $esdMinMax;
        }

        $shippingEstimate = (string)$pdplineitemObject->shipping_estimate;
        $currentMonth = date('M', $createdAtTimestamp);
        $currentYear = (int)date('Y', $createdAtTimestamp);
        $rangePattern = $this->getEsdRangePattern();
        $singleDatePattern = $this->getEsdSingleDatePattern();

        if (str_contains($shippingEstimate, '-')) {
            if (!preg_match($rangePattern, $shippingEstimate, $matches)) {
                $this->logEsdParseError($order, $item, 'invalid shipping_estimate format', [
                    'shipping_estimate' => $shippingEstimate,
                ]);
                return $esdMinMax;
            }

            [$esdMinMonth, $esdMinDay] = $this->parseEsdMonthDay($matches[1]);
            [$esdMaxMonth, $esdMaxDay] = $this->parseEsdMonthDay($matches[2]);

            if (!$this->isValidEsdMonthDay($esdMinMonth, $esdMinDay) || !$this->isValidEsdMonthDay($esdMaxMonth, $esdMaxDay)) {
                $this->logEsdParseError($order, $item, 'invalid shipping_estimate month or day', [
                    'shipping_estimate' => $shippingEstimate,
                    'esd_min' => $matches[1],
                    'esd_max' => $matches[2],
                ]);
                return $esdMinMax;
            }

            $minYear = $this->resolveEsdYear($currentMonth, $currentYear, $esdMinMonth);
            $maxYear = $this->resolveEsdYear($currentMonth, $currentYear, $esdMaxMonth);

            $esdMinMax['esdMin'] = $this->formatEsdDate(
                $esdMinMonth . ' ' . $esdMinDay . ' ' . $minYear,
                $order,
                $item,
                $shippingEstimate,
                'esd_min'
            );
            $esdMinMax['esdMax'] = $this->formatEsdDate(
                $esdMaxMonth . ' ' . $esdMaxDay . ' ' . $maxYear,
                $order,
                $item,
                $shippingEstimate,
                'esd_max'
            );

            return $esdMinMax;
        }

        if (preg_match($singleDatePattern, $shippingEstimate, $matches)) {
            [$esdMinMonth, $esdMinDay] = $this->parseEsdMonthDay($matches[1]);

            if (!$this->isValidEsdMonthDay($esdMinMonth, $esdMinDay)) {
                $this->logEsdParseError($order, $item, 'invalid shipping_estimate month or day', [
                    'shipping_estimate' => $shippingEstimate,
                    'esd_min' => $matches[1],
                ]);
                return $esdMinMax;
            }

            $minYear = $this->resolveEsdYear($currentMonth, $currentYear, $esdMinMonth);
            $esdMinMax['esdMin'] = $this->formatEsdDate(
                $esdMinMonth . ' ' . $esdMinDay . ' ' . $minYear,
                $order,
                $item,
                $shippingEstimate,
                'esd_min'
            );
        }

        return $esdMinMax;
    }

    private function isValidEsdMonth(string $month): bool
    {
        return isset($this->getMonths()[$month]);
    }

    private function isValidEsdMonthDay(string $month, string $day): bool
    {
        if (!$this->isValidEsdMonth($month)) {
            return false;
        }

        $dayInt = (int)$day;

        return $dayInt >= 1 && $dayInt <= 31;
    }

    private function normalizeEsdMonth(string $month): string
    {
        if ($month === '') {
            return '';
        }

        $normalized = ucfirst(strtolower($month));

        return $this->getEsdFullMonthNames()[$normalized] ?? $normalized;
    }

    private function getEsdFullMonthNames(): array
    {
        return [
            'January' => 'Jan',
            'February' => 'Feb',
            'March' => 'Mar',
            'April' => 'Apr',
            'May' => 'May',
            'June' => 'Jun',
            'July' => 'Jul',
            'August' => 'Aug',
            'September' => 'Sep',
            'October' => 'Oct',
            'November' => 'Nov',
            'December' => 'Dec',
        ];
    }

    private function parseEsdMonthDay(string $matchedDate): array
    {
        if (!preg_match('/^([A-Za-z]+)\s*(\d{1,2})(?!\d)/', trim($matchedDate), $matches)) {
            return ['', ''];
        }

        return [$this->normalizeEsdMonth($matches[1]), $matches[2]];
    }

    private function getEsdMonthDayPatternFragment(): string
    {
        $monthNames = array_keys($this->getMonths());
        foreach ($this->getEsdFullMonthNames() as $fullName => $abbreviation) {
            if ($fullName !== $abbreviation) {
                $monthNames[] = $fullName;
            }
        }

        usort($monthNames, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        $parts = [];
        foreach ($monthNames as $monthName) {
            $parts[] = $monthName . '\s*\d{1,2}(?!\d)';
        }

        return implode('|', $parts);
    }

    private function getEsdRangePattern(): string
    {
        $monthDay = $this->getEsdMonthDayPatternFragment();

        return '/(' . $monthDay . ')\s*-\s*(' . $monthDay . ')/i';
    }

    private function getEsdSingleDatePattern(): string
    {
        return '/(' . $this->getEsdMonthDayPatternFragment() . ')/i';
    }

    private function parseTimestamp(string $dateString): ?int
    {
        if ($dateString === '') {
            return null;
        }

        $timestamp = strtotime($dateString);

        return $timestamp !== false ? $timestamp : null;
    }

    private function resolveEsdYear(string $currentMonth, int $currentYear, string $esdMonth): int
    {
        $months = $this->getMonths();
        $year = $currentYear;

        if ($esdMonth === 'Jan' && $currentMonth === 'Dec') {
            $year = $currentYear + 1;
        }

        if ($currentMonth === $esdMonth) {
            $year = $currentYear;
        }

        if (isset($months[$esdMonth], $months[$currentMonth]) && $months[$currentMonth] > $months[$esdMonth]) {
            $year = $currentYear + 1;
        }

        return $year;
    }

    private function formatEsdDate(
        string $dateString,
        OrderInterface $order,
        $item,
        string $shippingEstimate,
        string $field
    ): string {
        $timestamp = $this->parseTimestamp($dateString);
        if ($timestamp === null) {
            $this->logEsdParseError($order, $item, 'invalid shipping_estimate date', [
                'field' => $field,
                'parsed_date' => $dateString,
                'shipping_estimate' => $shippingEstimate,
                'created_at' => (string)$item->getCreatedAt(),
            ]);

            return '';
        }

        try {
            return date('m/d/Y', $timestamp);
        } catch (\Throwable $e) {
            $this->logEsdParseError($order, $item, 'invalid shipping_estimate date format', [
                'field' => $field,
                'parsed_date' => $dateString,
                'shipping_estimate' => $shippingEstimate,
                'exception' => $e->getMessage(),
            ]);

            return '';
        }
    }

    private function logPluginError(OrderInterface $order, string $context, \Throwable $e): void
    {
        $message = sprintf(
            'Order attributes skipped (%s) | order_id=%s | increment_id=%s | error=%s',
            $context,
            (string)$order->getEntityId(),
            (string)$order->getIncrementId(),
            $e->getMessage()
        );
        $this->createLog($message);
    }

    private function decodeJsonObject(?string $json): ?\stdClass
    {
        if ($json === null || trim($json) === '') {
            return null;
        }

        $decoded = json_decode($json);

        return $decoded instanceof \stdClass ? $decoded : null;
    }

    private function getJsonValue(?\stdClass $object, string $property, $default = '')
    {
        if ($object === null || !property_exists($object, $property)) {
            return $default;
        }

        return $object->{$property} ?? $default;
    }

    private function logEsdParseError(OrderInterface $order, $item, string $reason, array $context = []): void
    {
        $message = sprintf(
            'ESD skipped (%s) | order_id=%s | increment_id=%s | item_id=%s | sku=%s | %s',
            $reason,
            (string)$order->getEntityId(),
            (string)$order->getIncrementId(),
            (string)$item->getItemId(),
            (string)$item->getSku(),
            http_build_query($context, '', ' | ')
        );
        $this->createLog($message);
    }

    public function setExtensionAttributes($order)
    {
        $items = $order->getItems();
        if (!$items) {
            return;
        }

        foreach ($items as $item) {
            try {
                $this->applyItemExtensionAttributes($order, $item);
            } catch (\Throwable $e) {
                $this->logPluginError($order, 'setExtensionAttributes item_id=' . (string)$item->getItemId(), $e);
            }
        }
    }

    private function applyItemExtensionAttributes(OrderInterface $order, $item): void
    {
        $extensionAttributes = $item->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->orderItemExtensionFactory->create();
        }

        $pdplineitemObject = $this->decodeJsonObject($item->getPdpLineItem());
        $shippingMetadataObject = $this->decodeJsonObject($item->getShippingMetadata());

        $extensionAttributes->setData('transactionId', $this->getJsonValue($shippingMetadataObject, 'transactionId', ' '));
        $extensionAttributes->setData('isValid', $this->getJsonValue($shippingMetadataObject, 'isValid', ' '));
        $extensionAttributes->setData('message', $this->getJsonValue($shippingMetadataObject, 'message', ' '));
        $extensionAttributes->setData('lineItemId', $this->getJsonValue($shippingMetadataObject, 'lineItemId', '0'));
        $extensionAttributes->setData('cost', $this->getJsonValue($shippingMetadataObject, 'cost', '0'));
        $extensionAttributes->setData('charge', $this->getJsonValue($shippingMetadataObject, 'charge', '0'));
        $extensionAttributes->setData('name', $this->getJsonValue($shippingMetadataObject, 'name', ' '));
        $extensionAttributes->setData('street', $this->getJsonValue($shippingMetadataObject, 'street', ' '));
        $extensionAttributes->setData('street2', $this->getJsonValue($shippingMetadataObject, 'street2', ' '));
        $extensionAttributes->setData('city', $this->getJsonValue($shippingMetadataObject, 'city', ' '));
        $extensionAttributes->setData('state', $this->getJsonValue($shippingMetadataObject, 'state', ' '));
        $extensionAttributes->setData('zipCode', $this->getJsonValue($shippingMetadataObject, 'zipCode', ' '));
        $extensionAttributes->setData('country', $this->getJsonValue($shippingMetadataObject, 'country', ' '));
        $extensionAttributes->setData('carrierName', $this->getJsonValue($shippingMetadataObject, 'carrierName', ' '));

        $extensionAttributes->setData('pricePerSqft', $this->getJsonValue($pdplineitemObject, 'PricePerSqft', 0));
        $extensionAttributes->setData('LineItemUnitQuantity', $this->getJsonValue($pdplineitemObject, 'LineItemUnitQuantity', 0));
        $extensionAttributes->setData('UnitPrice', $this->getJsonValue($pdplineitemObject, 'UnitPrice', 0));
        $extensionAttributes->setData('LineItemPrice', $this->getJsonValue($pdplineitemObject, 'LineItemPrice', 0));
        $extensionAttributes->setData('TileSize', $this->getJsonValue($pdplineitemObject, 'TileSize', ' '));
        $extensionAttributes->setData('Covers', $this->getJsonValue($pdplineitemObject, 'Covers', 0));
        $extensionAttributes->setData('SquareFootage', $this->getJsonValue($pdplineitemObject, 'SquareFootage', 0));
        $extensionAttributes->setData('Length', $this->getJsonValue($pdplineitemObject, 'Length', 0));
        $extensionAttributes->setData('Width', $this->getJsonValue($pdplineitemObject, 'Width', 0));
        $extensionAttributes->setData('PricePerLinearFoot', $this->getJsonValue($pdplineitemObject, 'PricePerLinearFoot', 0));
        $extensionAttributes->setData('RollWidth', $this->getJsonValue($pdplineitemObject, 'RoomWidth', 0));
        $extensionAttributes->setData('RollLength', $this->getJsonValue($pdplineitemObject, 'RoomLength', 0));
        $extensionAttributes->setData('RollType', $this->getJsonValue($pdplineitemObject, 'RollType', ' '));
        $extensionAttributes->setData('QuantityRange', $this->getJsonValue($pdplineitemObject, 'QuantityRange', 0));
        $extensionAttributes->setData('RollSize', $this->getJsonValue($pdplineitemObject, 'RollSize', 0));
        $extensionAttributes->setData('TrailerRollType', $this->getJsonValue($pdplineitemObject, 'TrailerRollType', ' '));
        $extensionAttributes->setData('CustomLength', $this->getJsonValue($pdplineitemObject, 'CustomLength', 0));
        $extensionAttributes->setData('CbcShopBy', $this->getJsonValue($pdplineitemObject, 'CBCShopBy', ' '));
        $extensionAttributes->setData('CbcTileSize', $this->getJsonValue($pdplineitemObject, 'CBCTileSize', ' '));
        $extensionAttributes->setData('BorderTileQty', $this->getJsonValue($pdplineitemObject, 'BorderTileQty', 0));
        $extensionAttributes->setData('CenterTileQty', $this->getJsonValue($pdplineitemObject, 'CenterTileQty', 0));
        $extensionAttributes->setData('CornerTileQty', $this->getJsonValue($pdplineitemObject, 'CornerTileQty', 0));
        $extensionAttributes->setData('promiseDate', $this->getJsonValue($pdplineitemObject, 'promise_date', ''));

        $getESDMinMax = $this->getESDMinMax($pdplineitemObject, $item, $order);

        $extensionAttributes->setData('esd_min', $getESDMinMax['esdMin'] ?? '');
        $extensionAttributes->setData('esd_max', $getESDMinMax['esdMax'] ?? '');

        if ($this->isRestIncrementIdSearchRequest()) {
            $this->createLog('REST API called for order: ' . $order->getIncrementId());
            $this->createLog('esd_min: ' . ($getESDMinMax['esdMin'] ?? ''));
            $this->createLog('esd_max: ' . ($getESDMinMax['esdMax'] ?? ''));
        }

        $itemOptions = $item->getProductOptions();

        $productName = '';
        $productSku = '';
        $productCategory = '';
        $productUrl = '';
        $productImage = '';
        $color_display_name = '';

        if ($itemOptions && !empty($itemOptions['additional_options']) && is_array($itemOptions['additional_options'])) {
            $sampleItemData = $itemOptions['additional_options'];
            $productName = $sampleItemData['main_configurable_product_name']['value'] ?? '';
            $productSku = $sampleItemData['configurable_product_sku']['value'] ?? '';
            $productCategory = $sampleItemData['configurable_product_category']['value'] ?? '';
            $productUrl = $sampleItemData['configurable_product_url']['value'] ?? '';
            $productImage = $sampleItemData['main_configurable_product_image']['value'] ?? '';
            $color_display_name = $sampleItemData['color_display_name']['value'] ?? '';
        }

        $extensionAttributes->setData('configurable_product_name', $productName);
        $extensionAttributes->setData('configurable_product_sku', $productSku);
        $extensionAttributes->setData('configurable_product_category', $productCategory);
        $extensionAttributes->setData('configurable_product_url', $productUrl);
        $extensionAttributes->setData('configurable_product_image', $productImage);
        $extensionAttributes->setData('color_display_name', $color_display_name);

        $item->setExtensionAttributes($extensionAttributes);
    }
	/**
     * Detect if request is the REST API order search by increment_id
     */
    protected function isRestIncrementIdSearchRequest(): bool
    {
        $path = $this->request->getPathInfo(); // e.g., /rest/V1/orders
        $params = $this->request->getParams();

        // Check endpoint path
        if (strpos($path, '/rest/V1/orders') === false) {
            return false;
        }

        // Validate query parameters
        if (
            isset($params['searchCriteria']['filter_groups'][0]['filters'][0]['field']) &&
            $params['searchCriteria']['filter_groups'][0]['filters'][0]['field'] === 'increment_id'
        ) {
            return true;
        }

        return false;
    }
	public function createLog($msg){
        try {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/orderAPI.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info($msg);
        } catch (\Throwable $e) {
            // Never break order API responses because logging failed.
        }
    }
}
