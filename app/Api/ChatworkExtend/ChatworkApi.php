<?php

namespace App\Api\ChatworkExtend;

use wataridori\ChatworkSDK\ChatworkApi as ChatworkApiBase;

class ChatworkApi extends ChatworkApiBase
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Delete Message.
     *
     * @param int room_id
     * @param int message_id
     *
     * @see http://developer.chatwork.com/ja/endpoint_rooms.html#DELETE-rooms-room_id-messages-message_id
     *
     * @return array
     */
    public function deleteMessage($roomId, $messageId)
    {
        return $this->api(
            sprintf('rooms/%d/messages/%s', $roomId, $messageId),
            ChatworkRequest::REQUEST_METHOD_DELETE
        );
    }
}
