<?php
declare(strict_types=1);

namespace Dcw\CustomAttribute\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class UsHolidays
{
    private const REGEX_PATTERN = '/^(0[1-9]|1[0-2])\/(0[1-9]|[12]\d|3[01])\/\d{4}(,(0[1-9]|1[0-2])\/(0[1-9]|[12]\d|3[01])\/\d{4})*$/';
    public const XML_PATH_DCW_US_HOLIDAY_HOLIDAY_US_HOLIDAY_LIST = 'dcw_us_holiday/holiday/us_holiday_list';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function getValue(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_DCW_US_HOLIDAY_HOLIDAY_US_HOLIDAY_LIST);
    }

    public function getValueValidated(): string
    {
        $usHolidayList = $this->getValue();

        if ($this->isValid($usHolidayList)) {
            return $usHolidayList;
        }

        return '';
    }

    private function isValid(string $value): bool
    {
        return (bool)preg_match(self::REGEX_PATTERN, $value);
    }

    public function getArray(): array
    {
        $value = $this->getValue();

        if (empty($value)) {
            return [];
        }

        return explode(',', $value);
    }

    public function getArrayValidated(): array
    {
        $valueValidated = $this->getValueValidated();

        if (empty($valueValidated)) {
            return [];
        }

        return explode(',', $this->getValueValidated());
    }
}
