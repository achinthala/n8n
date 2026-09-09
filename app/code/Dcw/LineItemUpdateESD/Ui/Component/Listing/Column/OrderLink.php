<?php

namespace Dcw\LineItemUpdateESD\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

/**
 * Class OrderLink
 *
 * Adds a custom link column to the Sales Order grid
 */
class OrderLink extends Column
{
    /**
     * URL builder instance used to generate admin URLs
     *
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Constructor
     *
     * @param UrlInterface $urlBuilder
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare data source for order grid column
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['entity_id'])) {

                    $url = $this->urlBuilder->getUrl(
                        'dcw_lineitemupdateesd/order/form',
                        ['order_id' => $item['entity_id']]
                    );

                    $item[$this->getData('name')] =
                        '<a href="' . $url . '" onclick="event.stopPropagation();">'
                        . __('Update Order')
                        . '</a>';
                }
            }
        }

        return $dataSource;
    }
}
