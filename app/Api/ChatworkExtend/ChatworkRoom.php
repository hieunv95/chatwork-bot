<?php

namespace App\Api\ChatworkExtend;

use wataridori\ChatworkSDK\ChatworkRoom as ChatworkRoomBase;
use Carbon\Carbon;
use App\Inspiring;

class ChatworkRoom extends ChatworkRoomBase
{
    const ME_ROOM_TYPE = 'me';
    const DIRECT_ROOM_TYPE = 'direct';

    /**
     * Constructor.
     */
    public function __construct($roomId)
    {
        parent::__construct($roomId);
        $this->chatworkApi = new ChatworkApi();
    }

    /**
     * Send messages to other members (except me).
     *
     * @param ChatworkUser $me
     *
     */
    public function sendMessageToOthers($me)
    {
        $message = "It's time to lunch. Get your ass off the chair! " . "\n" . Inspiring::quote();
        $otherMembers = collect($this->getMembers())->filter(function($member) use ($me) {
            return $member->account_id != $me['account_id'];
        });
        $this->sendMessageToList($otherMembers, $message, false, false);

        return $this;
    }

    /**
     * Delete Latest Message.
     *
     * @param int room_id
     * @param int message_id
     *
     */
    public function deleteLatestMessage()
    {
        $messages = collect($this->getMessages(true));
        if (count($messages) > 1) {
            $lastestMessage = $messages->last();
            $this->chatworkApi->deleteMessage($this->room_id, $lastestMessage->message_id);
        }

        return $this;
    }
}
