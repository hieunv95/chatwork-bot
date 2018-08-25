<?php

namespace App\Api\OpenWeatherMap;

use Gmopx\LaravelOWM\LaravelOWM;

class OpenWeatherMap extends LaravelOWM
{
    const SNOW_WEATHER_CODE = 600;

    /**
     * @var LaravelOWM
     */
    private $lowm;

    /**
     * @var \Cmfcmf\OpenWeatherMap\CurrentWeather
     */
    private $currentWeather;

    /**
     * OpenWeatherMap constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->lowm = new LaravelOWM();
        if (env('WEATHER_LATITUDE') && env('WEATHER_LONGTITUDE')) {
            $query = [
                'lat' => env('WEATHER_LATITUDE'),
                'lon' => env('WEATHER_LONGTITUDE'),
            ];
        } elseif (env('WEATHER_CITY')) {
            $query = env('WEATHER_CITY');
        } else {
            $query = 'hanoi,vn';
        }

        $this->currentWeather = $this->lowm->getCurrentWeather($query);
    }

    /**
     * @throws \Exception
     */
    public function getCurrentWeatherMessage()
    {
        $currentWeatherCode = $this->getCurrentMessageCode();
        $messages = Message::messagesByWeatherCode();
        if (isset($messages[$currentWeatherCode])) {
            return $messages[$currentWeatherCode];
        }

        return '';
    }

    public function getFormatedCurrentTemparature()
    {
        return $this->currentWeather->temperature->now->getFormatted();
    }

    public function isRaining()
    {
        return $this->getCurrentMessageCode() && $this->getCurrentMessageCode() < self::SNOW_WEATHER_CODE;
    }

    public function isHeavyRaining()
    {
        return in_array($this->getCurrentMessageCode(), [
            '202',
            '212',
            '502',
            '503',
            '504',
            '522',
        ]);
    }

    private function getCurrentMessageCode()
    {
        return $this->currentWeather->weather->id;
    }
}
