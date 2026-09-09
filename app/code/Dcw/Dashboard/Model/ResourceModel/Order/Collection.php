<?php

namespace Dcw\Dashboard\Model\ResourceModel\Order;

class Collection extends \Magento\Reports\Model\ResourceModel\Order\Collection
{
    /**
     * Prepare report summary
     *
     * @param string $range
     * @param mixed $customStart
     * @param mixed $customEnd
     * @param int $isFilter
     * @return $this
     */
    public function prepareSummary($range, $customStart, $customEnd, $isFilter = 0)
    {
        $this->checkIsLive($range);
        if ($this->_isLive) {
            $this->_prepareSummaryLive($range, $customStart, $customEnd, $isFilter);
        } else {
            $this->_prepareSummaryAggregated($range, $customStart, $customEnd);
        }

        return $this;
    }

    /**
     * Get range expression
     *
     * @param string $range
     * @return \Zend_Db_Expr
     */
    protected function _getRangeExpression($range)
    {
        switch ($range) {
            case 'today':
            case '24h':
                $expression = $this->getConnection()->getConcatSql(
                    [
                        $this->getConnection()->getDateFormatSql('{{attribute}}', '%Y-%m-%d %H:'),
                        $this->getConnection()->quote('00'),
                    ]
                );
                break;
            case '7d':
            case '1m':
                $expression = $this->getConnection()->getDateFormatSql('{{attribute}}', '%Y-%m-%d');
                break;
            case '60d':
                $expression = $this->getConnection()->getDateFormatSql('{{attribute}}', '%Y-%m-%d');
                break;
            case '1y':
            case '2y':
            case 'custom':
            default:
                $expression = $this->getConnection()->getDateFormatSql('{{attribute}}', '%Y-%m');
                break;
        }

        return $expression;
    }

    /**
     * Calculate From and To dates (or times) by given period
     *
     * @param string $range
     * @param string $customStart
     * @param string $customEnd
     * @param bool $returnObjects
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getDateRange($range, $customStart, $customEnd, $returnObjects = false)
    {
        $dateEnd = new \DateTime();
        $dateStart = new \DateTime();

        // go to the end of a day
        $dateEnd->setTime(23, 59, 59);

        $dateStart->setTime(0, 0, 0);

        switch ($range) {
            case 'today':
                $dateEnd->modify('now');
                break;
            case '24h':
                $dateEnd = new \DateTime();
                $dateEnd->modify('+1 hour');
                $dateStart = clone $dateEnd;
                $dateStart->modify('-1 day');
                break;

            case '7d':
                // substract 6 days we need to include
                // only today and not hte last one from range
                $dateStart->modify('-6 days');
                break;

            case '1m':
                $dateStart->setDate(
                    $dateStart->format('Y'),
                    $dateStart->format('m'),
                    $this->_scopeConfig->getValue(
                        'reports/dashboard/mtd_start',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    )
                );
                break;

            case 'custom':
                $dateStart = $customStart ? $customStart : $dateStart;
                $dateEnd = $customEnd ? $customEnd : $dateEnd;
                break;

            case '1y':
            case '2y':
                $startMonthDay = explode(
                    ',',
                    $this->_scopeConfig->getValue(
                        'reports/dashboard/ytd_start',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    )
                );
                $startMonth = isset($startMonthDay[0]) ? (int)$startMonthDay[0] : 1;
                $startDay = isset($startMonthDay[1]) ? (int)$startMonthDay[1] : 1;
                $dateStart->setDate($dateStart->format('Y'), $startMonth, $startDay);
                $dateStart->modify('-1 year');
                if ($range == '2y') {
                    $dateStart->modify('-1 year');
                }
                break;
            case '60d':
                $dateStart->modify('-60 day');
                break;
        }

        if ($returnObjects) {
            return [$dateStart, $dateEnd];
        } else {
            return ['from' => $dateStart, 'to' => $dateEnd, 'datetime' => true];
        }
    }
}
