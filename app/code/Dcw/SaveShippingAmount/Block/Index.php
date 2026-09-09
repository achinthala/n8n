<?php

namespace Dcw\SaveShippingAmount\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

class Index extends Template
{
    protected $resource;
    protected $request;
    protected $priceHelper;

    public function __construct(
        Template\Context $context,
        ResourceConnection $resource,
        Http $request, // For getting URL parameters
        PriceHelper $priceHelper,
        array $data = []
    ) {
        $this->resource = $resource;
        $this->request = $request;
        $this->priceHelper = $priceHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get data from amasty_quote table based on quote_id from URL
     *
     * @return array
     */
    public function getAmastyQuoteData($quoteId = null)
    {
        // If no quote ID is passed as an argument, get it from the URL parameter
        if (!$quoteId) {
            $quoteId = (int) $this->request->getParam('quote_id');
        }
        

        if (!$quoteId) {
            return [];
        }

        // Get the database connection
        $connection = $this->resource->getConnection();

        // Define the table name (with proper table prefix if any)
        $tableName = $connection->getTableName('amasty_quote');

        // Prepare and execute the query
        $select = $connection->select()
            ->from($tableName)
            ->where('quote_id = ?', $quoteId);

        // Fetch the result
        $result = $connection->fetchAll($select);

        return $result;
    }

    /**
     * Format price using Magento price helper
     *
     * @param float $amount
     * @return string
     */
    public function formatPrice($amount)
    {
        return $this->priceHelper->currency($amount, true, false);
    }
}
