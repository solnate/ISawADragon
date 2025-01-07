<?php

namespace App\Commands;

use App\Http\Controllers\BotController;
use App\Models\UserSession;
use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Start a new bot';
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
            'reply_markup' => BotController::getMenu()
        ]);
    }
}
