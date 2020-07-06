<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Order;
use Illuminate\Console\Command;

class DeleteOutdatedOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:delete-outdated-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete outdated orders';

    public function handle()
    {
        $orders = Order::where('type', Order::INIT_TYPE)
            ->where('created_at', '<', Carbon::today())
            ->get()->each(function ($order) {
                $order->children()->forceDelete();
                $order->delete();
            });

        $this->info("Deleted {$orders->count()} outdated orders.");
    }
}
