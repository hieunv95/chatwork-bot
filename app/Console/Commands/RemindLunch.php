<?php

namespace app\Console\Commands;

use App\Inspiring;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Console\Command;
use wataridori\ChatworkSDK\ChatworkSDK;
use App\Api\ChatworkExtend\ChatworkApi;
use App\Api\ChatworkExtend\ChatworkRoom;
use wataridori\ChatworkSDK\ChatworkUser;

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

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $tz = env('TZ') ?: 'Asia/Ho_Chi_Minh';
        try {
            switch (env('MESSAGE_LUNCH_TYPE')) {
                case self::TO_ALL_TYPE:
                    $this->sendMessageToAllByRoomID(env('ROOM_ID'));
                    break;
                case self::TO_DIRECT_TYPE:
                    $this->sendDirectMessages();
                    break;
                default:
                    return;
            }
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
            $message = '[toall]' . PHP_EOL . Inspiring::remindLunch($chatworkRoom->getMembersExceptMe());
            $chatworkRoom->sendMessageToAllWithShortcut(Message::REMIND_LUNCH_TYPE, $message);
        }
    }

    /**
     * @throws \wataridori\ChatworkSDK\Exception\ChatworkSDKException
     */
    private function sendDirectMessages()
    {
        $rooms = collect($this->chatworkApi->getRooms());
        $me = $this->chatworkApi->me();

        foreach ($rooms as $room) {
            if ($room['type'] == ChatworkRoom::DIRECT_ROOM_TYPE) {
                $chatworkRoom = new ChatworkRoom($room['room_id']);
                /** @var ChatworkUser $me */
                $chatworkRoom->deleteLatestMessage()->sendMessageToOthers($me);
            }
        }
    }
}
