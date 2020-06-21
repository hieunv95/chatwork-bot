<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return 'OK';
});

$router->post('/chatwork-hook/account-event', [
    'uses' => 'ChatworkController@handleWebhook',
    'middleware' => 'verifyChatworkWebhookSignature:account_event',
]);

$router->post('/chatwork-hook/room-event', [
    'uses' => 'ChatworkController@handleWebhook',
    'middleware' => 'verifyChatworkWebhookSignature:room_event',
]);
