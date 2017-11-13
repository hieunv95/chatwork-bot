<?php

namespace app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use wataridori\ChatworkSDK\ChatworkSDK;
use App\Api\ChatworkExtend\ChatworkApi;
use App\Api\ChatworkExtend\ChatworkRoom;
use App\Models\Reminder;

class RemindLunch extends Command
{
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
        ChatworkSDK::setApiKey(env("CHATWORK_API_KEY"));
        ChatworkSDK::setSslVerificationMode(false);
        $this->chatworkApi = new ChatworkApi();
    }

    public function handle()
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
