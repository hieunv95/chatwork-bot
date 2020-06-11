<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SchedulerDaemon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:daemon {--sleep=60}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Call the scheduler every minute.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tz = env('TZ') ?: 'Asia/Ho_Chi_Minh';
        $endRemindLunchTime = Carbon::createFromFormat('H:i', env('REMIND_LUNCH_TIME'), $tz)
            ->addMinute(5);
        $endRemindCheckoutTime = Carbon::createFromFormat('H:i', env('REMIND_CHECKOUT_TIME'), $tz)
            ->addMinute(5);
        while ($endRemindLunchTime->isFuture() || $endRemindCheckoutTime->isFuture()) {
            $this->line('<info>[' . Carbon::now($tz)->format('Y-m-d H:i:s') . ']</info> Calling scheduler');

            $this->call('schedule:run');

            sleep($this->option('sleep'));
        }
    }
}
