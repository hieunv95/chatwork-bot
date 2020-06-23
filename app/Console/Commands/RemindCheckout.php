<?php

namespace App\Console\Commands;

use App\Api\ChatworkExtend\ChatworkRoom;
use App\Exceptions\Handler;
use App\Inspiring;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemindCheckout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remind:checkout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remind checkout';

    public function handle()
    {
        $tz = env('TZ') ?: 'Asia/Ho_Chi_Minh';

        try {
            $this->sendMessageToAllByRoomID(env('ROOM_ID'));
        } catch (\Exception $e) {
            (new Handler())->report($e);
            $this->line('<info>[' . Carbon::now($tz)->format('Y-m-d H:i:s') . ']</info> Exception:' . PHP_EOL
                . '<error>' . $e->getMessage() . '</error>' . PHP_EOL . $e->getTraceAsString());
        }
    }

    /**
     * @param string|null $roomId
     *
     * @throws \Exception
     */
    private function sendMessageToAllByRoomID(string $roomId = null)
    {
        if (isset($roomId)) {
            $chatworkRoom = new ChatworkRoom($roomId);
            $message = 'TO ALL >>>' . PHP_EOL . Inspiring::remindCheckout();
            $chatworkRoom->sendMessageToAllWithShortcut(Message::REMIND_CHECKOUT, $message);
        }
    }
}
