<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use Services\DateService;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\RemindLunch::class,
        Commands\RemindUnipos::class,
        Commands\RemindCheckout::class,
        Commands\SchedulerDaemon::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $tz = env('TZ') ?: 'Asia/Ho_Chi_Minh';
        if (DateService::isNotHoliday() || DateService::isDateCompensation()) {
            $schedule->command('remind:lunch')
                ->dailyAt(env('REMIND_LUNCH_TIME'))
                ->timezone($tz);
            $schedule->command('remind:checkout')
                ->dailyAt(env('REMIND_CHECKOUT_TIME'))
                ->timezone($tz);
        }

        /*$schedule->command('remind:unipos')
            ->fridays()
            ->timezone($tz);*/
    }
}
