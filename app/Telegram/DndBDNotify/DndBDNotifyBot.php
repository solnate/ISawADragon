<?php

namespace App\Telegram\DndBDNotify;

use App\Helpers\Helper;
use App\Models\Notify;
use App\Models\UserSession;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Update;

class DndBDNotifyBot {
    protected Api $bot;
    protected Update $updates;
    protected Keyboard $menu;
    protected int $user_id = 0;
    protected ?UserSession $user;
    protected int $callbackId;
    protected int $chatId;
    protected int $messageId;
    protected string $callbackData;
    protected string $data;
    public function __construct(Api $bot)
    {
        $this->bot = $bot;
        $this->menu = self::getMenu();
    }
    public static function getMenu()
    {
        return Keyboard::make()
            //->setResizeKeyboard(true)
            //->setOneTimeKeyboard(true)
            ->inline()
            ->row([
                Keyboard::inlineButton(['text' => 'Создать напоминание', 'callback_data' => 'create'])
            ])
            ->row([
                Keyboard::inlineButton(['text' => 'Посмотреть напоминания', 'callback_data' => 'read'])
            ])
            ->row([
                Keyboard::inlineButton(['text' => 'Удалить напоминание', 'callback_data' => 'delete'])
            ]);
    }
    public function menu()
    {
        $text = "Меню";
        $this->bot->editMessageText([
            'chat_id' => $this->chatId,
            'message_id' => $this->messageId,
            'text' => $text,
            'reply_markup' => $this->menu
        ]);
        $this->bot->answerCallbackQuery(['callback_query_id' => $this->callbackId]);
    }
    public function handle(Update $update)
    {
        $this->updates = $update;
        if(!$isCallback = $this->setCallbackData())
            if(!$this->setData())
                return 'No data';

        $this->user = UserSession::where('user_id', $this->user_id)->first();
        if (!$this->user) return 'No user';

        if($isCallback && $this->user->state === UserSession::status['menu']) {
            $this->handleMenu();
        }
        else if($isCallback && $this->callbackData === 'menu') {
            $this->user->state = 'awaiting_command';
            $this->user->save();

            $this->bot->editMessageText([
                'chat_id' => $this->chatId,
                'message_id' => $this->messageId,
                'text' => 'Меню',
                'reply_markup' => $this->menu
            ]);

            $this->bot->answerCallbackQuery(['callback_query_id' => $this->callbackId]);
        }
        else {
            switch ($this->user->state) {
                case UserSession::status['creating']:
                    $content = Helper::getDate($this->data);
                    if($content) {
                        $this->user->state = 'awaiting_command';
                        $this->user->save();

                        Notify::create([
                            'user_id' => $this->user_id,
                            'date' => $content['date'],
                            'description' => $content['description'],
                        ]);

                        $text = 'Готово';
                    }
                    else
                        $text = 'Неверный формат';

                    $this->bot->sendMessage([
                        'chat_id' => $this->chatId,
                        'text' => $text,
                        'parse_mode' => 'HTML',
                        'reply_markup' => $this->menu
                    ]);
                    break;
                case UserSession::status['reading']:

                    break;
                case UserSession::status['deleting']:
                    $notify = Notify::find($this->callbackData);
                    if($notify) {
                        $notify->delete();
                        $text = 'Готово';
                    }
                    else
                        $text = 'Ошибка удаления';

                    $reply_markup = Keyboard::make()->inline();
                    $notifies = Notify::all();
                    if($notifies->isNotEmpty()) {
                        $this->makeNotifyKeyboard($notifies, $reply_markup);
                    }
                    else {
                        $text = "Больше ничего нет";
                    }
                    $reply_markup->row([
                        Keyboard::inlineButton(['text' => 'Назад в меню', 'callback_data' => 'menu'])
                    ]);

                    $this->bot->editMessageText([
                        'chat_id' => $this->chatId,
                        'message_id' => $this->messageId,
                        'text' => $text,
                        'reply_markup' => $reply_markup
                    ]);
                    $this->bot->answerCallbackQuery(['callback_query_id' => $this->callbackId]);
                    break;
            }
        }
        return true;
    }
    public function setCallbackData(): bool
    {
        if ($callbackQuery = $this->updates->getCallbackQuery()) {
            $this->user_id = $callbackQuery->getFrom()->getId();
            $this->chatId = $callbackQuery->getMessage()->getChat()->getId();
            $this->messageId = $callbackQuery->getMessage()->getMessageId();

            $this->callbackData = $callbackQuery->getData();
            $this->callbackId = $callbackQuery->getId();

            $requiredFields = ['user_id'];
            foreach ($requiredFields as $field) {
                if(!$this->$field) return false;
            }

            return true;
        }
        return false;
    }

    public function setData(): bool
    {
        $message = $this->updates->getMessage();
        $this->user_id = $message->getFrom()->getId();
        $this->chatId = $message->getChat()->getId();
        $this->messageId = $message->getMessageId();

        $this->data = $message->getText();

        $requiredFields = ['user_id', 'chatId', 'messageId'];
        foreach ($requiredFields as $field) {
            if(!$this->$field) return false;
        }

        return true;
    }

    public function handleMenu(): void
    {
        $reply_markup = Keyboard::make()->inline();
        switch($this->callbackData) {
            case 'create':
                $this->user->state = UserSession::status['creating'];
                $this->user->save();

                $text = "Теперь напиши дату и описание:";
                break;
            case 'read':
                $notifies = Notify::all();
                if($notifies->isNotEmpty()) {
                    $this->makeNotifyKeyboard($notifies, $reply_markup);
                    $text = "Список событий:";
                }
                else {
                    $text = "Ничего не найдено";
                }

                $this->user->state = UserSession::status['reading'];
                $this->user->save();
                break;
            case 'delete':
                $notifies = Notify::all();
                if($notifies->isNotEmpty()) {
                    $this->makeNotifyKeyboard($notifies, $reply_markup);
                    $text = "Выбери дату для удаления:";
                }
                else {
                    $text = "Ничего не найдено";
                }

                $this->user->state = UserSession::status['deleting'];
                $this->user->save();
                break;
        }

        $reply_markup->row([
            Keyboard::inlineButton(['text' => 'Назад в меню', 'callback_data' => 'menu'])
        ]);
        $this->bot->editMessageText([
            'chat_id' => $this->chatId,
            'message_id' => $this->messageId,
            'text' => $text,
            'reply_markup' => $reply_markup
        ]);

        $this->bot->answerCallbackQuery(['callback_query_id' => $this->callbackId]);
    }

    public function makeNotifyKeyboard(\Illuminate\Database\Eloquent\Collection $notifies, Keyboard &$reply_markup): void
    {
        $buttons = [];
        foreach ($notifies as $notify) {
            $message = $notify->date . ' | ' . $notify->description;
            $buttons[] = Keyboard::inlineButton(['text' => $message, 'callback_data' => $notify->id]);
        }
        foreach (array_chunk($buttons, 1) as $row) {
            $reply_markup->row($row);
        }
    }
}
