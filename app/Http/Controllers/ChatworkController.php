<?php

namespace App\Http\Controllers;

use Requests;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Api\ChatworkExtend\ChatworkApi;
use App\Api\ChatworkExtend\ChatworkRoom;

class ChatworkController extends Controller
{
    const WEBHOOK_KEYS = [
        'room_id' => 'webhook_event.room_id',
        'from_account_id' => 'webhook_event.from_account_id',
        'to_account_id' => 'webhook_event.to_account_id',
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
        $this->chatworkRoom->sendMessage($this->buildAnswerMessage());
    }

    /**
     * Build an answer message for replying the mention message.
     *
     * @return string
     */
    private function buildAnswerMessage()
    {
        $fromAccountId = $this->getWebhookVal('from_account_id');
        $messageId = $this->getWebhookVal('message_id');
        $messageBody = $this->getWebhookVal('message_body');
        $originalBotName = data_get($this->chatworkApi->me(), 'name') ?? '';
        $botName = preg_replace('/[^\pL\s]+/u', '', $originalBotName);
        $botName = trim($botName);
        $mainMentionContent = preg_replace('/\[.*?]/', '', $messageBody);
        $mainMentionContent = preg_replace('/\s+/', ' ', $mainMentionContent);
        $mainMentionContent = preg_replace('/' . $originalBotName . '/i', ' ', $mainMentionContent);
        $mainMentionContent = trim($mainMentionContent);

        switch (true) {
            case $mainMentionContent === '':
                $questionMarks = ['(?)', '(??)', '(???)'];
                $mainAnswerContent =  $questionMarks[mt_rand(0, count($questionMarks) - 1)];
                break;
            case strtolower($mainMentionContent) === 'del-msg':
                $mainAnswerContent = $this->deleteAnsweredMessages();
                break;
            default:
                $mainAnswerContent = $this->getAnswerFromSimi($mainMentionContent, $botName);
                break;
        }

        return "[rp aid={$fromAccountId} to={$this->roomId}-{$messageId}]" . PHP_EOL . $mainAnswerContent;
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
     * @param  string  $utext
     * @param  string  $botName
     * @return mixed|string|string[]|null
     */
    private function getAnswerFromSimi($utext = '', $botName = '')
    {
        $answerText = '';
        $headers = [
            'Content-Type' => 'application/json',
            'x-api-key' => env('SIMSIMI_API_KEY'),
        ];
        $data = '{
            "utext": "' . $utext . '", 
            "lang": "vn",
            "country": ["VN"],
            "atext_bad_prob_max": 0.1
        }';

        try {
            $response = Requests::post(
                env('SIMSIMI_API_ENDPOINT', 'https://wsapi.simsimi.com/190410/talk'),
                $headers,
                $data
            );
            $answerText = data_get(json_decode($response->body, true), 'atext') ?? '';
            $answerText = preg_replace('/simsimi|símimi|simimi|símini|simi|sisi|sim/i', $botName, $answerText);
        } catch (\Exception $e) {
            \Log::info($e->getMessage());
        }

        return in_array($answerText, ['', 'iy']) ? '(think)' : $answerText;
    }

    /**
     * Delete the answered messages by BOT.
     *
     * @return string
     */
    private function deleteAnsweredMessages()
    {
        $isAdminMember = $this->isAdminMember();
        $messageIds = $this->getMessageIndicesInMentionMessage();
        $messageIdsCount = count($messageIds);

        if (!$messageIdsCount) {
            return 'Không có tin nhắn nào em xóa được cả (dull)';
        }

        $deleteErrorCount = 0;
        $notDeletableCount = 0;

        foreach ($messageIds as $messageId) {
            try {
                if ($isAdminMember || $this->isDeletableMessage($messageId)) {
                    $this->chatworkApi->deleteMessage($this->roomId, $messageId);
                } else {
                    $notDeletableCount++;
                }
            } catch (\Exception $e) {
                $deleteErrorCount++;
                if ($deleteErrorCount === $messageIdsCount) {
                    \Log::info($e->getMessage());
                    return 'Chắc có lỗi gì rồi, em không xóa được tin nhắn đâu :(';
                }
            }
        }

        if ($notDeletableCount) {
            return 'Mội vài tin nhắn chính chủ vào xóa thì hay hơn ạ :v';
        }

        return 'Em đã xóa tin nhắn rồi nhé ;)';
    }

    /**
     * Get message IDs from mention message.
     *
     * @return array
     */
    private function getMessageIndicesInMentionMessage()
    {
        $aid = $this->getWebhookVal('to_account_id');
        $messageBody = $this->getWebhookVal('message_body');

        $messageIdPattern = '/\[rp aid=' . $aid . '.*to=' . $this->roomId . '-(.*?)]'
            . '|https:\/\/www.chatwork.com\/#!rid' . $this->roomId . '-(.*?)$/m';
        preg_match_all($messageIdPattern, $messageBody, $matches);

        $messageIdsFromReply = Arr::wrap($matches[1] ?? []);
        $messageIdsFromLink = Arr::wrap($matches[2] ?? []);
        $messageIds = array_merge($messageIdsFromReply, $messageIdsFromLink);

        array_walk($messageIds, function (&$messageId) {
            $messageId = preg_split('/\s+/', trim($messageId))[0] ?? '';
            $messageId = preg_replace('/[^0-9]/', '', $messageId);
        });

        return array_values(array_unique(array_filter($messageIds)));
    }

    /**
     * Check if the message is deletable.
     *
     * @param $messageId
     * @return bool
     */
    private function isDeletableMessage($messageId)
    {
        try {
            $fromAccountID = $this->getWebhookVal('from_account_id');
            $messageDetail = $this->chatworkApi->getRoomMessageByMessageId($this->roomId, $messageId);

            return strpos($messageDetail['body'] ?? '', '[rp aid=' . $fromAccountID) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the sender is the admin.
     *
     * @return bool
     */
    public function isAdminMember()
    {
        try {
            $fromAccountID = $this->getWebhookVal('from_account_id');
            $members = $this->chatworkApi->getRoomMembersById($this->roomId);
            $isAdminMember = collect($members)
                ->where('role', 'admin')
                ->where('account_id', $fromAccountID)
                ->count();

            return in_array($fromAccountID, explode(',', env('ADMIN_CHATWORK_ID', ''))) || $isAdminMember;
        } catch (\Exception $e) {
            return false;
        }
    }
}
