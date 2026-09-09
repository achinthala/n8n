<?php

declare(strict_types=1);

namespace Dcw\Dashboard\Plugin\Model\Dashboard;

use Magento\Backend\Model\Dashboard\Period;

class PeriodPlugin
{
    public const PERIOD_60_DAYS = '60d';

    private const PERIOD_UNIT_DAY = 'day';

    /**
     * @param Period $subject
     * @param array $result
     * @return array
     */
    public function afterGetDatePeriods(Period $subject, array $result): array
    {
        return array_merge($result, [self::PERIOD_60_DAYS => 'Last 60 days']);
    }

    /**
     * @param Period $subject
     * @param array $result
     * @return array
     */
    public function afterGetPeriodChartUnits(Period $subject, array $result): array
    {
        return array_merge($result, [self::PERIOD_60_DAYS => self::PERIOD_UNIT_DAY]);
    }
}
