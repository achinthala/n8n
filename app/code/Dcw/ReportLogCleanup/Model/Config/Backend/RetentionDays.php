<?php

declare(strict_types=1);

namespace Dcw\ReportLogCleanup\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

/**
 * Validates that retention period is at least 30 days.
 */
class RetentionDays extends Value
{
    private const MINIMUM_DAYS = 30;

    /**
     * @return Value
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = (int) $this->getValue();
        if ($value < self::MINIMUM_DAYS) {
            throw new LocalizedException(
                __("Retention Period (Days) must be at least %1 days.", self::MINIMUM_DAYS)
            );
        }
        return parent::beforeSave();
    }
}
