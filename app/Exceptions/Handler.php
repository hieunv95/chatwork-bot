<?php

namespace App\Exceptions;

use App\Api\ChatworkExtend\ChatworkRoom;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);

        if (env('APP_ENV') !== 'local' && $this->shouldReport($e)) {
            $reportRoom = new ChatworkRoom(env('ERROR_REPORT_ROOM_ID'));
            $reportMessage = implode(PHP_EOL, [
                '+ Env: ' . env('APP_ENV'),
                '+ Message: [code]' . $e->getMessage() . '[/code]',
                '+ File: ' . $e->getFile(),
                '+ Line: ' . $e->getLine(),
                '+ Logs site: '. env('LOG_SITE', 'https://dashboard.heroku.com/apps/chatwork-bot-remind/logs'),
            ]);
            $reportRoom->sendMessage(
                $reportRoom->buildInfo($reportMessage, get_class($e))
            );
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        return parent::render($request, $e);
    }
}
