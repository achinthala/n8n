<?php
/**
 * Force Unlock Action Column
 */

namespace Dcw\RequestQuote\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Dcw\RequestQuote\Service\QuoteLockService;

class ForceUnlock extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var QuoteLockService
     */
    protected $quoteLockService;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param QuoteLockService $quoteLockService
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        QuoteLockService $quoteLockService,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
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
                    $lockStatus = $this->quoteLockService->getAbsoluteLockStatus($quoteId);
                    
                    if ($lockStatus['is_locked']) {
                        $item[$this->getData('name')] = sprintf(
                            '<button type="button" class="action-secondary force-unlock-btn" 
                                    data-quote-id="%s" 
                                    onclick="forceUnlockQuoteFromGrid(%s)">
                                <span>%s</span>
                            </button>',
                            $quoteId,
                            $quoteId,
                            __('Force Unlock')
                        );
                    } else {
                        $item[$this->getData('name')] = '<span style="color: gray;">' . __('N/A') . '</span>';
                    }
                }
            }
        }

        return $dataSource;
    }
}

