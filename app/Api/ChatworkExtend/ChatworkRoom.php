<?php

namespace App\Api\ChatworkExtend;

use App\Inspiring;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use wataridori\ChatworkSDK\ChatworkRoom as ChatworkRoomBase;

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
     * {@inheritdoc}
     *
     * @return array
     */
    public function sendMessage($newMessage = null)
    {
        $message = $newMessage ? $newMessage : $this->message;
        return $this->chatworkApi->createRoomMessage($this->room_id, $message);
    }

    /**
     * Send messages to other members (except me).
     *
     * @param \wataridori\ChatworkSDK\ChatworkUser $me
     *
     * @return ChatworkRoom
     * @throws \wataridori\ChatworkSDK\Exception\ChatworkSDKException
     */
    public function sendMessageToOthers($me)
    {
        $message = "It's time to lunch. Get your ass off the chair! " . "\n" . Inspiring::quote();
        $otherMembers = collect($this->getMembers())->filter(function ($member) use ($me) {
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
     * @return ChatworkRoom
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

    public function sendMessageToAllWithShortcut()
    {
        try {
            DB::beginTransaction();
            $oldMessage = DB::table('messages')->first();
            if ($oldMessage && isset($oldMessage->id)) {
                $this->chatworkApi->deleteMessage($this->room_id, $oldMessage->id);
                DB::table('messages')->delete();
            }

            $message = '[toall]' . PHP_EOL . Inspiring::remindLunch();
            $currentMessage = $this->sendMessage($message);
            if ($currentMessage && isset($currentMessage['message_id'])) {
                DB::table('messages')->insert([
                    [
                        'id' => $currentMessage['message_id'],
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ],
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::debug($e->getMessage());
        }
    }
}
