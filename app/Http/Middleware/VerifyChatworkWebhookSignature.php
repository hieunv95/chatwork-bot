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
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $decodedWebhookToken = base64_decode(env('CHATWORK_WEBHOOK_TOKEN'));
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
