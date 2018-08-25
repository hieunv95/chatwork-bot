<?php

namespace App\Api\OpenWeatherMap;

class Message
{
    public static function messagesByWeatherCode()
    {
        return [
            '200' => 'có dông và mưa nhỏ',
            '201' => 'có dông và mưa',
            '202' => 'có dông và mưa to',
            '210' => 'có cơn dông nhỏ',
            '211' => 'có dông',
            '212' => 'có dông lớn',
            '221' => 'có lúc có dông',
            '230' => 'có dông và mưa phùn nhỏ',
            '231' => 'có dông và mưa phùn',
            '232' => 'có dông và mưa phùn',
            '300' => 'có mưa phùn',
            '301' => 'có mưa phùn',
            '302' => 'có mưa phùn',
            '310' => 'có mưa phùn',
            '311' => 'có mưa phùn',
            '312' => 'có mưa phùn',
            '313' => 'có mưa phùn',
            '314' => 'có mưa phùn',
            '321' => 'có mưa phùn',
            '500' => 'có mưa nhỏ',
            '501' => 'có mưa vừa',
            '502' => 'có mưa lớn',
            '503' => 'có mưa rất lớn',
            '504' => 'có mưa cực lớn',
            '511' => 'có mưa lạnh',
            '520' => 'có mưa rào nhỏ',
            '521' => 'có mưa rào',
            '522' => 'có mưa rào lớn',
            '531' => 'có lúc có mưa',
            '800' => 'quang mây',
            '801' => 'ít mây',
            '802' => 'có mây rải rác',
            '803' => 'có mây',
            '804' => 'nhiều mây',
        ];
    }
}
