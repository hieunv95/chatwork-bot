<?php

namespace Services;

use Carbon\Carbon;

class DateService
{
    const HOLIDAYS_2018 = [
        '0101',
        '1402',
        '1502',
        '1602',
        '1902',
        '2002',
        '2504',
        '3004',
        '0105',
        '1308',
        '1408',
        '0309',
        '3112',
    ];
    const HOLIDAYS_2019 = [
        '0101',
        '0402',
        '0502',
        '0602',
        '0702',
        '0802',
        '1504',
        '2904',
        '3004',
        '0105',
        '1208',
        '1308',
        '1408',
        '0209',
    ];
    const DATE_COMPENSATION_2019 = ['0501', '0405'];

    public static function isHoliday()
    {
        $now = Carbon::now();
        $date = $now->format('dm');
        $isWeekend = $now->isWeekend();

        return in_array($date, self::HOLIDAYS_2019) || $isWeekend;
    }

    public static function isNotHoliday()
    {
        return !self::isHoliday();
    }

    public static function isDateCompensation()
    {
        return in_array(Carbon::now()->format('dm'), self::DATE_COMPENSATION_2019);
    }
}
