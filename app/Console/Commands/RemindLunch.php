<?php

namespace app\Console\Commands;

use Illuminate\Console\Command;
use wataridori\ChatworkSDK\ChatworkSDK;
use App\Api\ChatworkExtend\ChatworkApi;
use App\Api\ChatworkExtend\ChatworkRoom;

class RemindLunch extends Command
{
    const TO_ALL_TYPE = 'toall';
    const TO_DIRECT_TYPE = 'direct';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remind:lunch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remind lunch';

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
        switch (env('MESSAGE_TYPE')) {
            case self::TO_ALL_TYPE:
                $this->sendMessageToAllByRoomID(env('ROOM_ID'));
                break;
            case self::TO_DIRECT_TYPE:
                $this->sendDirectMessages();
                break;
            default:
                return;
        }
    }

    private function sendMessageToAllByRoomID(string $roomId = null)
    {
        if (isset($roomId)) {
            $chatworkRoom = new ChatworkRoom($roomId);
            $chatworkRoom->sendMessageToAllWithShortcut();
        }
    }

    private function sendDirectMessages()
    {
        $rooms = collect($this->chatworkApi->getRooms());
        $me = $this->chatworkApi->me();

        foreach ($rooms as $room) {
            if ($room['type'] == ChatworkRoom::DIRECT_ROOM_TYPE) {
                $chatworkRoom = new ChatworkRoom($room['room_id']);
                $chatworkRoom->deleteLatestMessage()->sendMessageToOthers($me);
            }
        }
    }
}
