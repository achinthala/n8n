<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Model;

class ReasonDisplayFormatter
{
    public static function formatJson(?string $json): string
    {
        if ($json === null || $json === '') {
            return '';
        }
        $items = json_decode($json, true);
        if (!is_array($items)) {
            return '';
        }
        $labels = PendingReviewReason::labels();
        $parts = [];
        foreach ($items as $item) {
            $parts[] = self::formatReasonItem($item, $labels);
        }

        return implode(', ', array_filter($parts, static fn (string $s): bool => $s !== ''));
    }

    /**
     * Values for the admin grid select column / filter: restriction IDs and legacy string codes, comma-separated.
     *
     * @return non-empty-string|''
     */
    public static function selectValuesFromJson(?string $json): string
    {
        if ($json === null || $json === '') {
            return '';
        }
        $items = json_decode($json, true);
        if (!is_array($items)) {
            return '';
        }
        $parts = [];
        foreach ($items as $item) {
            if (is_string($item) && $item !== '') {
                $parts[] = $item;
                continue;
            }
            if (!is_array($item)) {
                continue;
            }
            if (($item[PendingReviewReason::FIELD_TYPE] ?? '') === PendingReviewReason::TYPE_ORDER_RESTRICTION) {
                $id = (int) ($item['restriction_id'] ?? 0);
                if ($id > 0) {
                    $parts[] = (string) $id;
                }
            }
        }

        return implode(',', $parts);
    }

    /**
     * @param string|array<string, mixed> $item
     * @param array<string, string> $labels
     */
    private static function formatReasonItem(array|string $item, array $labels): string
    {
        if (is_string($item)) {
            return $labels[$item] ?? $item;
        }
        if (!isset($item[PendingReviewReason::FIELD_TYPE])) {
            return '';
        }
        if ($item[PendingReviewReason::FIELD_TYPE] === PendingReviewReason::TYPE_ORDER_RESTRICTION) {
            return self::formatOrderRestrictionPayload($item);
        }

        return '';
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function formatOrderRestrictionPayload(array $item): string
    {
        $name = isset($item['name']) ? trim((string) $item['name']) : '';
        $title = isset($item['title']) ? trim((string) $item['title']) : '';
        if ($name === '' && $title === '') {
            $id = (int) ($item['restriction_id'] ?? 0);

            return $id > 0 ? (string) __('Order restriction rule #%1', $id) : '';
        }
        if ($name !== '' && $title !== '' && strcasecmp($name, $title) !== 0) {
            return $name . ' — ' . $title;
        }

        return $name !== '' ? $name : $title;
    }
}
