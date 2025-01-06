<?php

namespace App\Commands;

use App\Models\UserSession;
use Telegram\Bot\Commands\Command;

class DateAddCommand extends Command
{
    protected string $name = 'date_add';
    protected string $description = 'Adding date';
    /**
     * @inheritDoc
     */
    public function handle()
    {
        $userData = $this->getUpdate()->message->from;
        $user = UserSession::firstOrCreate(
            ['user_id' => $userData->id],
            ['state' => 'awaiting']
        );
        $user->state = 'awaiting';
        $user->save();
        $this->replyWithMessage([
            'text' => 'Теперь пришли дату и описание',
            'parse_mode' => 'HTML'
        ]);
    }
}
