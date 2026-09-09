<?php

declare(strict_types=1);

namespace Dcw\Dashboard\Plugin\Backend\Model\Dashboard\Chart;

use Magento\Backend\Model\Dashboard\Chart\Date;
use Dcw\Dashboard\Model\Dates;
use Dcw\Dashboard\Plugin\Model\Dashboard\PeriodPlugin;
class DatePlugin
{

    public function __construct(private readonly Dates $dates)
    {
    }

    /**
     * @param Date $subject
     * @param array $result
     * @param string $period
     * @return array
     */
    public function afterGetByPeriod(Date $subject, array $result, string $period): array
    {
        if ($period == PeriodPlugin::PERIOD_60_DAYS) {
            $result = array_reverse($this->dates->getLast60Dates());
        }
        return $result;
    }
}
