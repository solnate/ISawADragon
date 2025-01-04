<?php

namespace App\Commands;

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
        $this->replyWithMessage([
           'text' => 'Starting new bot...',
            'parse_mode' => 'HTML'
        ]);
    }
}
