<?php

namespace App\Api\ChatworkExtend;

use App\Inspiring;
use App\Models\Message;
use wataridori\ChatworkSDK\ChatworkRoom as ChatworkRoomBase;

class ChatworkRoom extends ChatworkRoomBase
{
    const ME_ROOM_TYPE = 'me';
    const DIRECT_ROOM_TYPE = 'direct';

    /**
     * @var Message
     */
    private $messageModel;

    /**
     * Constructor.
     *
     * @param $roomId
     */
    public function __construct($roomId)
    {
        parent::__construct($roomId);
        $this->chatworkApi = new ChatworkApi();
        $this->messageModel = new Message();
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

    public function sendMessageToAllWithShortcut(int $messageType = null, string $messageContent = '')
    {
        try {
            $messageQuery = $this->messageModel->where([
                'room_id' => $this->room_id,
                'type' => $messageType,
            ]);
            $oldMessage = $messageQuery->first();
            if ($oldMessage && isset($oldMessage->id)) {
                $this->chatworkApi->deleteMessage($this->room_id, $oldMessage->id);
                $messageQuery->delete();
            }
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }

        $currentMessage = $this->sendMessage($messageContent);
        if ($currentMessage && isset($currentMessage['message_id'])) {
            $this->messageModel->create([
                'id' => $currentMessage['message_id'],
                'room_id' => $this->room_id,
                'type' => $messageType,
            ]);
        }
    }

    /**
     * Get members of the room except me.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getMembersExceptMe()
    {
        $me = $this->chatworkApi->me();
        return collect($this->getMembers())->filter(function ($member) use ($me) {
            return $member->account_id != $me['account_id'];
        });
    }
}
