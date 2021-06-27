<?php

namespace App\Api\ChatworkExtend;

use wataridori\ChatworkSDK\ChatworkApi as ChatworkApiBase;
use wataridori\ChatworkSDK\Exception\RequestFailException;

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

    protected function api($endPoint, $method = ChatworkRequest::REQUEST_METHOD_GET, $params = [])
    {
        $request = new ChatworkRequest(self::$apiKey);
        $request->setEndPoint($endPoint);
        $request->setMethod($method);
        $request->setParams($params);

        try {
            $response = $request->send();
        } catch (RequestFailException $e) {
            if (str_contains($e->getMessage(), 'The message is not found')) {
                return false;
            }

            throw $e;
        }

        return $response['response'];
    }
}
