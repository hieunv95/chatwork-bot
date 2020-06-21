<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class VerifyChatworkWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure  $next
     * @param  string  $eventType
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $eventType = 'account_event')
    {
        $token = '';

        if ($eventType === 'account_event') {
            $token = env('CHATWORK_WEBHOOK_ACCOUNT_EVEN_TOKEN');
        } elseif ($eventType === 'room_event') {
            $token = env('CHATWORK_WEBHOOK_ROOM_EVENT_TOKEN');
        }

        $decodedWebhookToken = base64_decode($token);
        $signatureFromHeader = $request->header('X-Chatworkwebhooksignature');
        $signatureFromBodyDigest = base64_encode(
            hash_hmac('sha256', $request->getContent(), $decodedWebhookToken, true)
        );

        if ($signatureFromHeader !== $signatureFromBodyDigest) {
            return response('The signature is invalid.', Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
