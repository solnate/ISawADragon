<?php

namespace App\Commands;

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
        $this->replyWithMessage([
            'text' => 'Теперь пришли дату и описание',
            'parse_mode' => 'HTML'
        ]);
    }
}
