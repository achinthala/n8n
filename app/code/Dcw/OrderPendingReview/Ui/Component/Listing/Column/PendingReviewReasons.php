<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Ui\Component\Listing\Column;

use Dcw\OrderPendingReview\Model\ReasonDisplayFormatter;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class PendingReviewReasons extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items']) || !is_array($dataSource['data']['items'])) {
            return $dataSource;
        }
        foreach ($dataSource['data']['items'] as &$item) {
            $raw = isset($item['dcw_pending_review_reasons']) ? (string) $item['dcw_pending_review_reasons'] : '';
            // Select column matches row values to option values (rule IDs / legacy codes), not formatted labels.
            $item['dcw_pending_review_reasons'] = ReasonDisplayFormatter::selectValuesFromJson(
                $raw !== '' ? $raw : null
            );
        }
        unset($item);

        return $dataSource;
    }
}
