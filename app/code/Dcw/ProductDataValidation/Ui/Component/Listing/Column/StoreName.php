<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Renders store view name for result rows.
 */
class StoreName extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $names = [];
        foreach ($dataSource['data']['items'] as &$item) {
            $storeId = (int) ($item['store_id'] ?? 0);
            if (!isset($names[$storeId])) {
                try {
                    $names[$storeId] = $this->storeManager->getStore($storeId)->getName();
                } catch (\Throwable) {
                    $names[$storeId] = (string) $storeId;
                }
            }
            $item[$this->getData('name')] = $names[$storeId];
        }

        return $dataSource;
    }
}
