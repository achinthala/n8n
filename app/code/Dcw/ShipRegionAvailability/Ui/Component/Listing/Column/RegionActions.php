<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class RegionActions extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['region_id'])) {
                continue;
            }
            $name = $this->getData('name');
            $item[$name]['edit'] = [
                'href' => $this->urlBuilder->getUrl(
                    'dcw_shipregion/region/edit',
                    ['region_id' => $item['region_id']]
                ),
                'label' => __('Edit'),
            ];
            $item[$name]['delete'] = [
                'href' => $this->urlBuilder->getUrl(
                    'dcw_shipregion/region/delete',
                    ['region_id' => $item['region_id']]
                ),
                'label' => __('Delete'),
                'confirm' => [
                    'title' => __('Delete Region'),
                    'message' => __('Are you sure you want to delete this region?'),
                ],
            ];
        }

        return $dataSource;
    }
}
