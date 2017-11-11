<?php

namespace app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use wataridori\ChatworkSDK\ChatworkRoom;
use wataridori\ChatworkSDK\ChatworkSDK;
use wataridori\ChatworkSDK\ChatworkApi;
use Carbon\Carbon;

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

    protected $chatworkRoom;
    protected $chatworkApi;
    protected $roomId = 89867570;

    public function __construct()
    {
        parent::__construct();
        ChatworkSDK::setApiKey(env("CHATWORK_API_KEY"));
        $this->chatworkRoom = new ChatworkRoom($this->roomId);
        $this->chatworkApi = new ChatworkApi();
    }

    public function handle()
    {
        $message = "It's time to lunch. Get your ass off the chair! " . Carbon::now('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s');
        $members = $this->chatworkRoom->getMembers();
        $me = $this->chatworkApi->me();
        $otherMembers = collect($members)->filter(function($member) use ($me) {
            return $member->account_id != $me['account_id'];
        });
        $this->chatworkRoom->sendMessageToList($otherMembers, $message, false, false);
    }

}
