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
    protected Api $telegram;
    protected UserSession $user;
    protected array $data;
    protected int $chat_id;
    protected Keyboard $menuKeyboard;

    /**
     * Create a new controller instance.
     *
     * @param  Api  $telegram
     */
    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
        $this->menuKeyboard = self::getMenu();
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
        $this->data = $request->all();
        $user_id = (int)data_get($this->data, 'message.from.id');
        $this->chat_id = (int)data_get($this->data, 'message.chat.id');

        $this->user = UserSession::where('user_id', $user_id)->first();
        if (!$this->user) return;
        switch ($this->user->state) {
            case UserSession::status['menu']:
                $this->menu();
                break;
            case UserSession::status['creating']:
                $this->create($user_id);
                break;
            case UserSession::status['deleting']:
                if(data_get($this->data, 'message.text') === 'Назад в меню')
                    $this->SetMenuStatus();
                else
                    $this->delete();
                break;
        }
    }
    public function menu(): void
    {
        switch (data_get($this->data, 'message.text')) {
            case 'Посмотреть напоминания':
                $this->read();
                break;
            case 'Создать напоминание':
                $this->setCreatingStatus();
                break;
            case 'Удалить напоминание':
                $this->SetDeletingOrMenuStatus();
                break;
        }
    }
    public function create(int $user_id): void
    {
        $content = Helper::getDate(data_get($this->data, 'message.text'));
        if ($content) {
            Notify::create([
                'user_id' => $user_id,
                'date' => $content['date'],
                'description' => $content['description'],
            ]);

            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => 'Добавлено',
                'parse_mode' => 'HTML'
            ]);

            $this->read();
        }
        else {
            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => 'Неправильный формат',
                'parse_mode' => 'HTML'
            ]);
        }
    }
    public function read(): void
    {
        $notifies = Notify::all();
        if ($notifies->isNotEmpty()) {
            $message = '';
            foreach ($notifies as $notify) {
                $message .= $notify->id . '. ' . $notify->date . ' -> ' . $notify->description . PHP_EOL;
            }
            $this->sendMessage($this->chat_id, $message);
        }

        $this->SetMenuStatus();
    }
    public function delete(): void
    {
        $id = strstr(data_get($this->data, 'message.text'), '.', true);
        $notify = Notify::find($id);
        if($notify) {
            $notify->delete();
            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => 'Удалено',
                'parse_mode' => 'HTML'
            ]);
        }
        else
            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => 'Ошибка удаления',
                'parse_mode' => 'HTML'
            ]);

        $this->SetDeletingOrMenuStatus();
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
    public function SetMenuStatus(): void
    {
        $this->user->state = UserSession::status['menu'];
        $this->user->save();
        $this->telegram->sendMessage([
            'chat_id' => $this->chat_id,
            'text' => 'Меню',
            'parse_mode' => 'HTML',
            'reply_markup' => $this->menuKeyboard
        ]);
    }
    public function setCreatingStatus(): void
    {
        $this->user->state = UserSession::status['creating'];
        $this->user->save();

        $this->sendMessage($this->chat_id, 'Теперь пришли дату и описание');
    }
    public function SetDeletingOrMenuStatus(): void
    {
        $notifiesKeyboard = $this->getNotifiesKeyboard();
        if ($notifiesKeyboard) {
            $this->user->state = UserSession::status['deleting'];
            $this->user->save();
            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => 'Выбери напоминание для удаления',
                'parse_mode' => 'HTML',
                'reply_markup' => $this->getNotifiesKeyboard()
            ]);
        }
        else {
            $this->telegram->sendMessage([
                'chat_id' => $this->chat_id,
                'text' => 'Ничего не найдено',
                'parse_mode' => 'HTML'
            ]);

            $this->SetMenuStatus();
        }
    }
}
