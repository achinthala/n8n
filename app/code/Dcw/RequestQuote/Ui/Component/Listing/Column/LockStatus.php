<?php
/**
 * Lock Status Column
 */

namespace Dcw\RequestQuote\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Dcw\RequestQuote\Service\QuoteLockService;

class LockStatus extends Column
{
    /**
     * @var QuoteLockService
     */
    protected $quoteLockService;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param QuoteLockService $quoteLockService
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        QuoteLockService $quoteLockService,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->quoteLockService = $quoteLockService;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['entity_id'])) {
                    $quoteId = $item['entity_id'];
                    // Use getAbsoluteLockStatus instead of getLockStatus to avoid session dependency
                    // This works correctly even when cached (e.g., Fastly) where sessions might not be available
                    try {
                        $lockStatus = $this->quoteLockService->getAbsoluteLockStatus($quoteId);
                        if ($lockStatus['is_locked']) {
                            $lockText = __('Locked by %1 (%2)', 
                                $lockStatus['locked_by_name'] ?: 'Unknown',
                                $lockStatus['locked_by_type'] === 'admin' ? 'Admin' : 'Customer'
                            );
                            $item[$this->getData('name')] = '<span style="color: red;">' . $lockText . '</span>';
                        } else {
                            $item[$this->getData('name')] = '<span style="color: green;">' . __('Available') . '</span>';
                        }
                    } catch (\Exception $e) {
                        $item[$this->getData('name')] = '<span style="color: gray;">' . __('Unknown') . '</span>';
                    }
                }
            }
        }

        return $dataSource;
    }
}

