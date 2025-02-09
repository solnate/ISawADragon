<?php

namespace App\Http\Controllers;

use App\Telegram\DndBDNotify\DndBDNotifyBot;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;
class WebhookController extends Controller
{
    public function handle(Request $request, string $token) : Response
    {
        Log::info('Запрос получен', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        try {
            $api = new Api($token);
            $botData = $api->getMe();

            if($botData->username === 'DndBDNotifyBot') {
                $bot = Telegram::bot($botData->username);
                $updates = $bot->getWebhookUpdate();
                $message = $updates->getMessage();

                if ($message && str_starts_with($message->getText(), '/')) {
                    $bot->commandsHandler(true);
                }
                else {
                    $botController = new DndBDNotifyBot($bot);
                    return response($botController->handle($updates),200);
                }

                return response($message,200);
            }

            return response('Not Handled',200);
        } catch (TelegramSDKException $e) {
            return response($e,502);
        }
    }
}
