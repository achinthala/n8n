<?php

declare(strict_types=1);

namespace Dcw\Dashboard\Model;

class Dates
{
    /**
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        $dateStart = new \DateTime();
        $dateStart->modify('now');
        $dateStart->setTime(0, 0, 0);
        return $dateStart;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        $dateEnd = new \DateTime();
        $dateEnd->modify('now');
        $dateEnd->modify('-60 days');
        $dateEnd->setTime(23, 59, 59);
        return $dateEnd;
    }

    /**
     * @return array
     */
    public function getLast60Dates(): array
    {
        $dates = [];
        $startDate = $this->getStartDate();

        for ($i = 0; $i < 60; $i++) {
            $date = clone $startDate;
            $date->modify("-$i days");
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }
}
