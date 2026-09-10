<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Job grid action links.
 */
class JobActions extends Column
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

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['job_id'])) {
                continue;
            }
            $jobId = (int) $item['job_id'];
            $item[$this->getData('name')] = [
                'status' => [
                    'href' => $this->urlBuilder->getUrl(
                        'product_data_validation/validation/status',
                        ['job_id' => $jobId]
                    ),
                    'label' => __('View Status'),
                ],
                'report' => [
                    'href' => $this->urlBuilder->getUrl(
                        'product_data_validation/report/index',
                        ['job_id' => $jobId]
                    ),
                    'label' => __('View Report'),
                ],
            ];
        }

        return $dataSource;
    }
}
