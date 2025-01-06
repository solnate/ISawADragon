<?php

namespace App\Http\Controllers;

use App\Models\Notify;
use App\Models\UserSession;
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
            $this->Notify($request, $controller);

        Telegram::bot($bot->username)->commandsHandler(true);
        return response($bot,200);
    }

    public function Notify(Request $request, BotController $controller): void
    {
        $data = $request->all();
        $user_id = data_get($data, 'message.from.id');

        $user = UserSession::where('user_id', $user_id)->first();
        if ($user) {
            switch ($user->state) {
                case 'awaiting':
                    $content = self::getDate(data_get($data, 'message.text'));
                    if($content) {
                        $date = new Notify;
                        $date->user_id = $user_id;
                        $date->date = $content['date'];
                        $date->description = $content['description'];
                        $date->save();

                        $user->state = 'closed';
                        $user->save();

                        $message = 'Готово';
                    } else {
                        $message = 'Неправильный формат';
                    }
                    $controller->sendMessage(data_get($data, 'message.chat.id'), $message);
                    break;
            }
        }

    }
    public static function getDate(string $message)
    {
        preg_match('/\d{1,4}[\/\-\.]?\d{1,2}[\/\-\.]?\d{1,4}/', $message, $matches);
        if (isset($matches[0])) {
            $date = $matches[0]; // Дата
            $description = substr($message, strlen($date) + 1);
            return ['date' => $date, 'description' => $description];
        } else {
            return null;
        }
    }
}
