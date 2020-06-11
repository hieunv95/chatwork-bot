<?php

namespace App\Console\Commands;

use App\Api\ChatworkExtend\ChatworkApi;
use App\Api\ChatworkExtend\ChatworkRoom;
use App\Inspiring;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Console\Command;
use wataridori\ChatworkSDK\ChatworkSDK;

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

    protected $chatworkApi;

    public function __construct()
    {
        parent::__construct();
        ChatworkSDK::setApiKey(env('CHATWORK_API_KEY'));
        ChatworkSDK::setSslVerificationMode(false);
        $this->chatworkApi = new ChatworkApi();
    }

    public function handle()
    {
        $tz = env('TZ') ?: 'Asia/Ho_Chi_Minh';

        try {
            $this->sendMessageToAllByRoomID(env('ROOM_ID'));
        } catch (\Exception $e) {
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
