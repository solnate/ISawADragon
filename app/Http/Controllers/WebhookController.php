<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Telegram\Bot\Api;
use Telegram\Bot\Laravel\Facades\Telegram;

class WebhookController extends Controller
{
    public function handle(Request $request, string $token) : Response
    {
        $api = new Api($token);
        $controller = new BotController($api);
        $bot = $controller->show();

        if($bot->username === 'DndBDNotifyBot')
            $controller->notify($request);

        $update = Telegram::bot($bot->username)->commandsHandler(true);
        return response($update,200);
    }
}
