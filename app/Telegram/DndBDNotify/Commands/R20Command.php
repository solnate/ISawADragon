<?php

namespace App\Telegram\DndBDNotify\Commands;

use Telegram\Bot\Commands\Command;

class R20Command extends Command
{
    protected string $name = 'r20';
    protected string $description = 'Roll a 20';
    /**
     * @inheritDoc
     */
    public function handle()
    {
        $text = rand(1, 20);
        $this->replyWithMessage([
            'text' => $text,
            'parse_mode' => 'HTML'
        ]);
    }
}
