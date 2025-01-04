<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/webhook', WebhookController::class)->name('webhook');

Route::get('/bot', function () {
    return Telegram\Bot\Laravel\Facades\Telegram::bot('DndBDNotifyBot')->getMe();
});

Route::get('/send', function () {
    $token = '7951958405:AAEEISseWG0WqBDpcu0stYRCP0sn6c06-Qo';
    $chatId = '-1002470032008';
    $data = http_build_query([
       'text' => 'this is a test',
       'chat_id' => $chatId,
    ]);
    $url = 'https://api.telegram.org/bot' . $token . '/sendMessage?' . $data;
    file_get_contents($url);
    return response('OK', 200)->header('Content-Type', 'text/plain');
});
