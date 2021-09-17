<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
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
        'account_id' => 'webhook_event.account_id',
    ];

    const TO_ALL_REGEX_PATTERN = '(\[toall\]|TO ALL >>>|TO ALL>>>|TOALL >>>|TOALL>>>)';
    const ORDER_REGEX_PATTERN = '(order|đặt|thực đơn)';
    const CONFIRMED_REGEX_PATTERN = '(confirm|confirmed|chot|chốt)';

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
        $this->request = $request;
        $this->roomId = $this->getWebhookVal('room_id');
        $this->chatworkRoom = new ChatworkRoom($this->roomId);
        $this->chatworkApi = new ChatworkApi();
        $eventType = $this->getWebhookVal('webhook_event_type');

        if ($eventType === 'mention_to_me') {
            $this->replyToMentionMessage();
        } elseif ($eventType === 'message_created' || $eventType === 'message_updated') {
            $this->handleCreatedAndUpdatedMessageEvents();
        }
    }

    private function deleteChangedOrderMessages()
    {
        $orderModel = new Order();

        return $orderModel->where('message_id', $this->getWebhookVal('message_id'))
            ->where('type', Order::INIT_TYPE)
            ->delete();
    }

    private function isDeletedMessage($messageId)
    {
        $orderModel = new Order();

        $message = $this->chatworkApi->getRoomMessageByMessageId(
            $this->getWebhookVal('room_id'),
            $messageId
        );

        if (data_get($message, 'body') === '[deleted]') {
            $order = $orderModel->where('message_id', $messageId)
                ->where('type', Order::INIT_TYPE)
                ->first();

            if ($order) {
                $order->children()->forceDelete();
                $order->forceDelete();

                return true;
            }
        }

        return false;
    }

    private function handleCreatedAndUpdatedMessageEvents()
    {
        $orderModel = new Order();
        $messageBody = $this->getWebhookVal('message_body');
        $lowerMessageBody = mb_strtolower($messageBody);

        if (preg_match(self::TO_ALL_REGEX_PATTERN, $messageBody) === 1
            && preg_match(self::ORDER_REGEX_PATTERN, $lowerMessageBody) === 1) {
            $this->trackToAllInitialOrder();
        } elseif (!$this->deleteChangedOrderMessages()) {
            preg_match('/\[rp aid=.*to=.*-(.*?)]/m', $messageBody, $repliedMessageIdMatches);
            $repliedMessageId = $repliedMessageIdMatches[1] ?? null;

            if ($repliedMessageId && !$this->isDeletedMessage($repliedMessageId)) {
                $initialOrder = $orderModel
                    ->withTrashed()
                    ->where(['message_id' => $repliedMessageId, 'type' => Order::INIT_TYPE])
                    ->first();

                if (!$initialOrder) {
                    $order = $orderModel
                        ->withTrashed()
                        ->where('message_id', $repliedMessageId)
                        ->whereIn('type', [Order::PREVIEW_TYPE, Order::CONFIRMED_TYPE])
                        ->first();
                    $initialOrder = $order ? ($order->parentOrder()->withTrashed()->first() ?? null) : null;
                }

                $orderOwerName = $initialOrder->account_name ?? '';
                $mainContent = preg_replace('/\[.*?]/', '', $messageBody);
                $mainContent = str_replace($orderOwerName, '', $mainContent);
                $mainContent = trim(preg_replace('/\s+/', '', $mainContent));
                preg_match_all('/(?<=|^)[-+]\d+(?=|$)/', $mainContent, $quantityMatches);
                $doesContainValidOrderQuantity = is_numeric(trim($quantityMatches[0][0] ?? false));

                if ($initialOrder && $initialOrder->deleted_at) {
                    $this->sendOutdatedOrderWarning();
                } elseif ($doesContainValidOrderQuantity && $initialOrder) {
                    $this->trackRegisteredOrder($initialOrder, $quantityMatches);
                } elseif (!$doesContainValidOrderQuantity && $initialOrder
                    && $orderModel->where('message_id', $this->getWebhookVal('message_id'))->delete()) {
                    $this->sendPreviewOrderMessage($initialOrder);
                } else {
                    $this->trackConfirmedOrder($repliedMessageId);
                }
            }
        }
    }

    private function sendOutdatedOrderWarning()
    {
        $roomId = $this->getWebhookVal('room_id');
        $accountId = $this->getWebhookVal('account_id');
        $messageId = $this->getWebhookVal('message_id');
        $this->chatworkRoom->sendMessage(
            "[rp aid={$accountId} to={$roomId}-{$messageId}]"
            . PHP_EOL . 'Đơn đã hết hạn hoặc không tồn tại (shake)'
        );
    }

    private function trackToAllInitialOrder()
    {
        $messageId = $this->getWebhookVal('message_id');
        $roomId = $this->getWebhookVal('room_id');
        $accountName = data_get(
            $this->chatworkApi->getRoomMessageByMessageId($roomId, $messageId),
            'account.name'
        );
        $orderModel = new Order();
        $doesExistMessage = $orderModel->withTrashed()->where('message_id', $messageId)->exists();
        $messageData = [
            'message_id' => $messageId,
            'room_id' => $roomId,
            'account_id' => $this->getWebhookVal('account_id'),
            'account_name' => $accountName,
            'type' => Order::INIT_TYPE,
            'deleted_at' => null,
        ];

        if ($doesExistMessage) {
            $orderModel->withTrashed()->where('message_id', $messageId)->update($messageData);
        } else {
            $orderModel->create($messageData);
        }
    }

    private function sendPreviewOrderMessage($initialOrder)
    {
        $orderModel = new Order();
        $roomId = $this->getWebhookVal('room_id');

        $initialOrder->previewOrders->each(function ($order) use ($roomId) {
            $this->chatworkApi->deleteMessage($roomId, $order->message_id);
        });
        $initialOrder->previewOrders()->delete();
        $validOrders = $initialOrder
            ->registeredOrders()
            ->select('account_id', DB::raw('SUM(ordered_quantity)'))
            ->groupBy('account_id')
            ->havingRaw('SUM(ordered_quantity) > ?', [0])
            ->pluck('account_id')
            ->toArray();
        $registeredOrders = $initialOrder
            ->registeredOrders
            ->map(function ($order) use ($roomId, $orderModel, $validOrders) {
                if (!in_array($order->account_id, $validOrders)) {
                    return false;
                }

                $repliedMessage = $this->chatworkApi->getRoomMessageByMessageId(
                    $roomId,
                    $order->message_id
                );

                if (data_get($repliedMessage, 'body') === '[deleted]') {
                    $orderModel->where('message_id', $order->message_id)->forceDelete();

                    return false;
                }

                return $order;
            })
            ->filter();
        $previewContent = $this->buildPreviewOrderMessage($initialOrder, $registeredOrders);

        if ($previewContent) {
            $previewOrderMessage = $this->chatworkRoom->sendMessage($previewContent);
            $orderModel->create([
                'message_id' => data_get($previewOrderMessage, 'message_id'),
                'type' => Order::PREVIEW_TYPE,
                'parent_order_id' => $initialOrder->getKey(),
            ]);
        }

        $accountId = $this->getWebhookVal('account_id');
        $messageId = $this->getWebhookVal('message_id');

        if ($initialOrder->status === Order::CONFIRMED_STATUS && $initialOrder->account_id !== $accountId) {
            $this->chatworkRoom->sendMessage(
                "[rp aid={$accountId} to={$roomId}-{$messageId}]"
                . PHP_EOL . 'Đơn đã chốt rồi. Có thể cần chốt lại ạ.'
                . PHP_EOL . "CC: [picon:{$initialOrder->account_id}]"
            );
        }
    }

    private function trackRegisteredOrder(Order $initialOrder, $orderQuantities)
    {
        $orderModel = new Order();
        $messageId = $this->getWebhookVal('message_id');
        $roomId = $this->getWebhookVal('room_id');
        $accountName = data_get(
            $this->chatworkApi->getRoomMessageByMessageId($roomId, $messageId),
            'account.name'
        );
        $doesExistMessage = $orderModel->withTrashed()->where('message_id', $messageId)->exists();
        $messageData = [
            'message_id' => $messageId,
            'room_id' => $roomId,
            'account_id' => $this->getWebhookVal('account_id'),
            'account_name' => $accountName,
            'type' => Order::REGISTER_TYPE,
            'parent_order_id' => $initialOrder->getKey(),
            'ordered_quantity' => collect($orderQuantities)->flatten()->sum(),
            'deleted_at' => null,
        ];

        if ($doesExistMessage) {
            $orderModel->withTrashed()->where('message_id', $messageId)->update($messageData);
        } else {
            $orderModel->create($messageData);
        }

        $this->sendPreviewOrderMessage($initialOrder);
    }

    private function trackConfirmedOrder(int $previewOrderMessageId)
    {
        $roomId = $this->getWebhookVal('room_id');
        $messageBody = mb_strtolower($this->getWebhookVal('message_body'));
        $orderModel = new Order();
        $previewOrderMessage = $orderModel
            ->where(['message_id' => $previewOrderMessageId, 'type' => Order::PREVIEW_TYPE])
            ->first();

        if ($previewOrderMessage && preg_match(self::CONFIRMED_REGEX_PATTERN, $messageBody) === 1
            && $this->canConfirmOrder($previewOrderMessage->parentOrder)) {
            $parentOrderId = $previewOrderMessage->parent_order_id;
            $registeredOrders = $orderModel->where('parent_order_id', $parentOrderId)
                ->where('type', Order::REGISTER_TYPE)
                ->get();
            $confirmedOrderContent = $this->buildConfirmOrderMessage(
                $previewOrderMessage->parentOrder,
                $registeredOrders
            );
            $orderModel->where('type', Order::CONFIRMED_TYPE)->where('parent_order_id', $parentOrderId)->get()
                ->each(function ($order) use ($roomId) {
                    $this->chatworkApi->deleteMessage($roomId, $order->message_id);
                });
            $orderModel->where('type', Order::CONFIRMED_TYPE)->where('parent_order_id', $parentOrderId)->delete();
            $confirmedOrderMessage = $this->chatworkRoom->sendMessage($confirmedOrderContent);
            $orderModel->create([
                'message_id' => data_get($confirmedOrderMessage, 'message_id'),
                'type' => Order::CONFIRMED_TYPE,
                'parent_order_id' => $parentOrderId,
            ]);
            $previewOrderMessage->parentOrder()->update(['status' => Order::CONFIRMED_STATUS]);
        }
    }

    private function canConfirmOrder($order)
    {
        $accountId = $this->getWebhookVal('account_id');

        if ($accountId === $order->account_id || $this->isAdminMember($accountId)) {
            return true;
        }

        $roomId = $this->getWebhookVal('room_id');
        $messageId = $this->getWebhookVal('message_id');
        $this->chatworkRoom->sendMessage("[rp aid={$accountId} to={$roomId}-{$messageId}]"
            . PHP_EOL . 'Chỉ người tạo order hoặc Admin mới có thể chốt. (bow)');

        return false;
    }

    private function buildConfirmOrderMessage($initialOrder, $registeredOrders)
    {
        $validOrders = $initialOrder
            ->registeredOrders()
            ->select('account_id', DB::raw('SUM(ordered_quantity)'))
            ->groupBy('account_id')
            ->havingRaw('SUM(ordered_quantity) > ?', [0])
            ->pluck('account_id')
            ->toArray();
        $registeredOrders = collect()->wrap($registeredOrders);
        $orderedQuantityTotal = 0;
        $memberList = $registeredOrders->map(function ($order) use ($validOrders, &$orderedQuantityTotal) {
            if (!in_array($order->account_id, $validOrders)) {
                return false;
            }

            $orderedQuantityTotal += $order->ordered_quantity;

            return '[rp aid=' . $order->account_id . ' to=' . $order->room_id . '-'
                . $order->message_id . '] ' . $order->account_name
                . ' : ' . $order->ordered_quantity;
        })->filter()->implode(PHP_EOL);
        $orderTime = $initialOrder->created_at->format('d/m/Y');

        return implode(PHP_EOL, [
            "[info][title]Chốt đơn hàng ($orderTime)[/title]" .
            "(*) Người tạo đơn hàng: [piconname:{$initialOrder->account_id}]",
            "(*) Link đặt hàng: https://www.chatwork.com/#!rid{$initialOrder->room_id}-{$initialOrder->message_id}",
            '(*) Danh sách đặt:',
            $memberList,
            "[hr](handshake) Tổng số lượng đặt : {$orderedQuantityTotal}",
            '[/info]',
        ]);
    }

    private function buildPreviewOrderMessage($initialOrder, $registeredOrders)
    {
        $registeredOrders = collect()->wrap($registeredOrders);
        $memberList = $registeredOrders->map(function ($order) {
            return '* ' . $order->account_name . ' : ' . $order->ordered_quantity;
        })->implode(PHP_EOL);
        $orderedQuantityTotal = $registeredOrders->sum('ordered_quantity');

        if ($orderedQuantityTotal < 1) {
            return false;
        }

        $orderTime = $initialOrder->created_at->format('d/m/Y');

        return implode(PHP_EOL, [
            "[info][title]Xem trước đơn hàng ($orderTime)[/title]" .
            "(*) Người tạo đơn hàng: {$initialOrder->account_name}",
            "(*) Link đặt hàng: https://www.chatwork.com/#!rid{$initialOrder->room_id}-{$initialOrder->message_id}",
            '(*) Danh sách đặt:',
            $memberList,
            "[hr](*) Tổng số lượng đặt : {$orderedQuantityTotal}",
            '[info](lightbulb) Người tạo đơn hàng trả lời "chốt" (hoặc "chot") vào tin nhắn này để hoàn tất đơn hàng ạ.'
            . ';)[/info]',
            '[/info]',
        ]);
    }

    /**
     * Reply to mention message.
     */
    private function replyToMentionMessage()
    {
        if (($answerMessage = $this->buildAnswerMessage()) !== false) {
            $this->chatworkRoom->sendMessage($answerMessage);
        }
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
        preg_match('/\[rp aid=.*to=.*-(.*?)]/m', $messageBody, $repliedMessageIdMatches);
        $repliedMessageId = $repliedMessageIdMatches[1] ?? null;
        $lowerMessageBody = mb_strtolower($messageBody);
        preg_match_all('/(?<=|^)[-+]\d+(?=|$)/', $mainMentionContent, $quantityMatches);
        $doesContainValidOrderQuantity = is_numeric(trim($quantityMatches[0][0] ?? false));
        $isNotOrderMessage = preg_match(self::TO_ALL_REGEX_PATTERN, $messageBody) !== 1
            && preg_match(self::ORDER_REGEX_PATTERN, $lowerMessageBody) !== 1
            && preg_match(self::CONFIRMED_REGEX_PATTERN, $lowerMessageBody) !== 1
            && !$doesContainValidOrderQuantity;

        switch (true) {
            case $mainMentionContent === '':
                $questionMarks = ['(?)', '(??)', '(???)'];
                $mainAnswerContent =  $questionMarks[mt_rand(0, count($questionMarks) - 1)];
                break;
            case strtolower($mainMentionContent) === 'del-msg':
                $mainAnswerContent = $this->deleteAnsweredMessages();
                break;
            case $isNotOrderMessage || Order::withTrashed()->where('message_id', $repliedMessageId)->doesntExist():
                $mainAnswerContent = $this->getAnswerFromSimi($mainMentionContent, $botName);
                break;
            default:
                return false;
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

        return $this->request->json(self::WEBHOOK_KEYS[$key] ?? $key, $default);
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
            //'x-api-key' => env('SIMSIMI_API_KEY'),
        ];
        /*$data = '{
            "utext": "' . $utext . '", 
            "lang": "vn",
            "atext_bad_prob_max": 0.0
        }';*/

        try {
            $response = Requests::get(
                env('SIMSIMI_API_ENDPOINT', 'https://api.simsimi.net/v2') . '?lc=vn&lang=vi_VN&text=' . $utext,
                $headers
            );
            $answerText = data_get(json_decode($response->body, true), 'success') ?? '';
            $answerText = preg_replace('/simsimi|símimi|simimi|símini|simi|sisi|smsimi|sim/i', $botName, $answerText);
        } catch (\Exception $e) {
            \Log::info($e->getMessage());
        }

        return in_array($answerText, ['', 'iy', 'ir']) ? '(think)' : $answerText;
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
                    (new Handler())->report($e);
                    \Log::info($e->getMessage());
                    return 'Chắc có lỗi gì rồi, em không xóa được tin nhắn đâu :(';
                }
            }
        }

        if ($notDeletableCount) {
            return 'Mội vài tin nhắn, chính chủ vào xóa thì hay hơn ạ :v';
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
            (new Handler())->report($e);
            return false;
        }
    }

    /**
     * Check if the sender is the admin.
     *
     * @param  null  $accountId
     *
     * @return bool
     */
    public function isAdminMember($accountId = null)
    {
        try {
            $fromAccountID = $accountId ?? $this->getWebhookVal('from_account_id');
            $members = $this->chatworkApi->getRoomMembersById($this->roomId);
            $isAdminMember = collect($members)
                ->where('role', 'admin')
                ->where('account_id', $fromAccountID)
                ->count();

            return in_array($fromAccountID, explode(',', env('ADMIN_CHATWORK_ID', ''))) || $isAdminMember;
        } catch (\Exception $e) {
            (new Handler())->report($e);
            return false;
        }
    }
}
