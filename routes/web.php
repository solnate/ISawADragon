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

Route::get('/test', function () {
    return env('REDIS_CLIENT');
});
