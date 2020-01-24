<?php

namespace Services;

use Carbon\Carbon;
use peterkahl\Lunar\Lunar;

class DateService
{
    const GREGORIAN_HOLIDAYS = [
        '0101',
        '3004',
        '0105',
        '0209',
    ];

    const LUNAR_HOLIDAYS = [
        '3012',
        '0101',
        '0201',
        '0301',
        '0401',
        '1003',
    ];

    /**
     * Check if the current date is the holiday.
     *
     * @return bool
     */
    public static function isHoliday()
    {
        $now = Carbon::now();
        $isWeekend = $now->isWeekend();
        $date = $now->format('dm');
        $dateWithYear =  $now->format('dmy');
        $lunarDate = Lunar::Gregorian2Lunar($now->format('Y-m-d'));
        $lunarDate = $lunarDate['d'] . $lunarDate['m'];

        return $isWeekend || in_array($date, self::GREGORIAN_HOLIDAYS)
            || in_array($lunarDate, self::LUNAR_HOLIDAYS)
            || in_array($dateWithYear, explode(',', env('HOLIDAYS')));
    }

    /**
     * Check if the current date is not the holiday.
     *
     * @return bool
     */
    public static function isNotHoliday()
    {
        return !self::isHoliday();
    }

    /**
     * Check if the current date is compensation date.
     *
     * @return bool
     */
    public static function isDateCompensation()
    {
        return in_array(Carbon::now()->format('dmy'), explode(',', env('COMPENSATION_DATES')));
    }
}
