<?php

namespace App\Http\Controllers;

use Requests;
use Illuminate\Http\Request;
use App\Api\ChatworkExtend\ChatworkApi;
use App\Api\ChatworkExtend\ChatworkRoom;

class ChatworkController extends Controller
{
    const WEBHOOK_KEYS = [
        'room_id' => 'webhook_event.room_id',
        'from_account_id' => 'webhook_event.from_account_id',
        'message_id' => 'webhook_event.message_id',
        'message_body' => 'webhook_event.body',
    ];

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ChatworkApi
     */
    private $chatworkApi;

    /**
     * @var ChatworkRoom
     */
    private $chatworkRoom;

    /**
     * @var string
     */
    private $roomId;

    /**
     * Handle Chatwork webhook request.
     *
     * @param Request $request
     */
    public function handleWebhook(Request $request)
    {
        try {
            $this->request = $request;
            $this->roomId = $this->getWebhookVal('room_id');
            $this->chatworkRoom = new ChatworkRoom($this->roomId);
            $this->chatworkApi = new ChatworkApi();
            $this->replyToMentionMessage();
        } catch (\Exception $e) {
            \Log::info($e->getMessage());
        }
    }

    /**
     * Reply to mention message.
     */
    private function replyToMentionMessage()
    {
        $this->chatworkRoom->sendMessage($this->buildReplyMentionMessage());
    }

    /**
     * Build reply text for mention message.
     *
     * @return string
     */
    private function buildReplyMentionMessage()
    {
        $fromAccountId = $this->getWebhookVal('from_account_id');
        $messageId = $this->getWebhookVal('message_id');
        $messageBody = $this->getWebhookVal('message_body');

        return "[rp aid={$fromAccountId} to={$this->roomId}-{$messageId}]" . PHP_EOL
            . $this->getAnswerFromSimi($messageBody);
    }

    /**
     * Get value in Chatwork webhook payload.
     *
     * @param  null  $key
     * @param  null  $default
     *
     * @return mixed
     */
    private function getWebhookVal($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->request->json();
        }

        return $this->request->json(self::WEBHOOK_KEYS[$key] ?? null, $default);
    }

    /**
     * Get random anwser from Simsimi API.
     *
     * @param  string  $mentionMesssage
     *
     * @return mixed|string|string[]|null
     */
    private function getAnswerFromSimi($mentionMesssage = '')
    {
        $botName = data_get($this->chatworkApi->me(), 'name') ?? '';
        $botName = preg_replace('/[^\pL\s]+/u', '', $botName);
        $botName = trim($botName);
        $utext = preg_replace('/\[.*?]/', '', $mentionMesssage);
        $utext = preg_replace('/\s+/', ' ', $utext);
        $utext = preg_replace('/' . $botName . '/', ' ', $utext);
        $utext = trim($utext);
        if ($utext === '') {
            return '(??)';
        }

        $answerText = '';
        $headers = [
            'Content-Type' => 'application/json',
            'x-api-key' => env('SIMSIMI_API_KEY'),
        ];
        $data = '{
            "utext": "' . $utext . '", 
            "lang": "vn"
        }';

        try {
            $response = Requests::post('https://wsapi.simsimi.com/190410/talk', $headers, $data);
            $answerText = data_get(json_decode($response->body, true), 'atext') ?? '';
            $answerText = preg_replace('/sim|símimi|simimi|simi|simsimi|símini/i', $botName, $answerText);
        } catch (\Exception $e) {
            \Log::info($e);
        }

        return $answerText === '' ? '(think)' : $answerText;
    }
}
