<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Notify;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

class BotController extends Controller
{
    protected $telegram;

    /**
     * Create a new controller instance.
     *
     * @param  Api  $telegram
     */
    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Show the bot information.
     */
    public function show()
    {
        return $this->telegram->getMe();
    }
    public function sendMessage($chat_id, $message)
    {
        $this->telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);
    }

    public function notify(Request $request)
    {
        $data = $request->all();
        $user_id = (int)data_get($data, 'message.from.id');

        $user = UserSession::where('user_id', $user_id)->first();
        if (!$user) return;
        switch ($user->state) {
            case UserSession::statuses['command']:
                $this->commands($data, $user);
                break;
            case UserSession::statuses['create']:
                $this->create($data, $user_id, $user);
                break;
            case UserSession::statuses['delete']:
                if(data_get($data, 'message.text') === 'Назад в меню') {
                    $this->SetCommandStatus($user, $data);
                }
                else{
                    $this->delete($data, $user);
                    $this->SetDeleteStatus($user, $data);
                }
                break;
        }
    }

    public function commands(array $data, UserSession $user): void
    {
        switch (data_get($data, 'message.text')) {
            case 'Посмотреть напоминания':
                $this->read($data);
                break;
            case 'Создать напоминание':
                $user->state = UserSession::statuses['create'];
                $user->save();
                $this->sendMessage(data_get($data, 'message.chat.id'), 'Теперь пришли дату и описание');
                break;
            case 'Удалить напоминание':
                $this->SetDeleteStatus($user, $data);
                break;
        }
    }

    public function create(array $data, int $user_id, UserSession $user): void
    {
        $content = Helper::getDate(data_get($data, 'message.text'));
        if ($content) {
            Notify::create([
                'user_id' => $user_id,
                'date' => $content['date'],
                'description' => $content['description'],
            ]);

            $this->telegram->sendMessage([
                'chat_id' => data_get($data, 'message.chat.id'),
                'text' => 'Добавлено',
                'parse_mode' => 'HTML'
            ]);

            $this->read($data);

            $this->SetCommandStatus($user, $data);
        } else {
            $this->telegram->sendMessage([
                'chat_id' => data_get($data, 'message.chat.id'),
                'text' => 'Неправильный формат',
                'parse_mode' => 'HTML'
            ]);
        }
    }

    public function delete(array $data, UserSession $user): void
    {
        $id = strstr(data_get($data, 'message.text'), '.', true);
        $notify = Notify::find($id);
        if($notify) {
            $notify->delete();
            $this->telegram->sendMessage([
                'chat_id' => data_get($data, 'message.chat.id'),
                'text' => 'Удалено',
                'parse_mode' => 'HTML'
            ]);
        }
        else
            $this->telegram->sendMessage([
                'chat_id' => data_get($data, 'message.chat.id'),
                'text' => 'Ошибка удаления',
                'parse_mode' => 'HTML'
            ]);
    }
    public function read(array $data): void
    {
        $message = '';
        $notifies = Notify::all();
        foreach ($notifies as $notify) {
            $message .= $notify->id . '. ' . $notify->date . ' -> ' . $notify->description . PHP_EOL;
        }
        if(empty($message)) $message = 'Ничего не найдено';
        $this->sendMessage(data_get($data, 'message.chat.id'), $message);
    }

    public function getNotifiesKeyboard()
    {
        $notifies = Notify::all();
        if($notifies->isNotEmpty()) {
            $reply_markup = Keyboard::make()
                ->setResizeKeyboard(true)
                ->setOneTimeKeyboard(true);
            foreach ($notifies as $notify) {
                $message = $notify->id . '. ' . $notify->date . ' -> ' . $notify->description . PHP_EOL;
                $reply_markup->row([
                    Keyboard::button($message),
                ]);
            }
            $reply_markup->row([
                Keyboard::button('Назад в меню'),
            ]);
            return $reply_markup;
        }
        else
            return null;
    }

    public static function getMenu()
    {
        return Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->row([
                Keyboard::button('Создать напоминание'),
            ])
            ->row([
                Keyboard::button('Посмотреть напоминания'),
            ])
            ->row([
                Keyboard::button('Удалить напоминание'),
            ]);
    }
    public function SetCommandStatus($user, array $data): void
    {
        $user->state = UserSession::statuses['command'];
        $user->save();
        $this->telegram->sendMessage([
            'chat_id' => data_get($data, 'message.chat.id'),
            'text' => 'Меню',
            'parse_mode' => 'HTML',
            'reply_markup' => BotController::getMenu()
        ]);
    }
    public function SetDeleteStatus($user, array $data): void
    {
        $notifiesKeyboard = $this->getNotifiesKeyboard();
        if ($notifiesKeyboard) {
            $user->state = UserSession::statuses['delete'];
            $user->save();
            $this->telegram->sendMessage([
                'chat_id' => data_get($data, 'message.chat.id'),
                'text' => 'Выбери напоминание для удаления',
                'parse_mode' => 'HTML',
                'reply_markup' => $this->getNotifiesKeyboard()
            ]);
        } else {
            $this->telegram->sendMessage([
                'chat_id' => data_get($data, 'message.chat.id'),
                'text' => 'Ничего не найдено',
                'parse_mode' => 'HTML'
            ]);

            $this->SetCommandStatus($user, $data);
        }
    }
}
