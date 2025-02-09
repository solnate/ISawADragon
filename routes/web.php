<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Telegram\Bot\Laravel\Facades\Telegram;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/{token}/webhook', [WebhookController::class, 'handle'])->name('webhook');

Route::get('/bot', function () {
    return Telegram::bot('DndBDNotifyBot')->getMe();
});

Route::get('/bot/{name}', function ($name) {
    return Telegram::bot($name)->setWebhook(
        ['url' => config('app.url') . config('telegram.bots.' . $name . '.token') . '/webhook']
    );
});
