<?php

namespace app\Console\Commands;

use App\Api\ChatworkExtend\ChatworkApi;
use App\Api\ChatworkExtend\ChatworkRoom;
use App\Inspiring;
use App\Models\Message;
use Illuminate\Console\Command;
use wataridori\ChatworkSDK\ChatworkSDK;

class RemindUnipos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remind:unipos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remind sharing Unipos points';

    protected $chatworkApi;

    public function __construct()
    {
        parent::__construct();
        ChatworkSDK::setApiKey(env('CHATWORK_API_KEY'));
        ChatworkSDK::setSslVerificationMode(false);
        $this->chatworkApi = new ChatworkApi();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $roomId = env('ROOM_ID');
        if (isset($roomId)) {
            $chatworkRoom = new ChatworkRoom($roomId);
            $url = 'https://unipos.me/all';
            $message = '[toall]' . PHP_EOL . Inspiring::remindUnipos($chatworkRoom->getMembersExceptMe())
                . PHP_EOL . $url;
            $chatworkRoom->sendMessageToAllWithShortcut(Message::REMIND_UNIPOS_TYPE, $message);
        }
    }
}
