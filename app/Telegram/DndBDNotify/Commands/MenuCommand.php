<?php

namespace App\Telegram\DndBDNotify\Commands;

use App\Models\UserSession;
use App\Telegram\DndBDNotify\DndBDNotifyBot;
use Telegram\Bot\Commands\Command;

class MenuCommand extends Command
{
    protected string $name = 'menu';
    protected string $description = 'Show menu';
    /**
     * @inheritDoc
     */
    public function handle()
    {
        $userData = $this->getUpdate()->message->from;
        $user = UserSession::firstOrCreate(
            ['user_id' => $userData->id],
            ['state' => 'awaiting_command']
        );
        $user->state = 'awaiting_command';
        $user->save();
        $this->replyWithMessage([
            'text' => 'Меню',
            'parse_mode' => 'HTML',
            'reply_markup' => DndBDNotifyBot::getMenu()
        ]);
    }
}
