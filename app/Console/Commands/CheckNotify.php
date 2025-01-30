<?php

namespace App\Console\Commands;

use App\Models\Notify;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Telegram\Bot\Laravel\Facades\Telegram;

class CheckNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentDate = Carbon::now();
        $notifies = Notify::whereBetween('date', [$currentDate, $currentDate->copy()->addDays(7)])->get();

        foreach($notifies as $notify) {
            $date = Carbon::parse($notify->date);
            $daysLeft = ceil($currentDate->diffInDays($date));
            if ($date->greaterThan($currentDate)) {
                if($daysLeft == 1) {
                    $text = "{$notify->description} уже завтра!";
                }
                else {
                    $text = "{$notify->description} наступит через {$daysLeft} дней!";
                }

                $bot = Telegram::bot('DndBDNotifyBot');
                $bot->sendMessage([
                    'chat_id' => '-1002470032008',
                    'text' => $text,
                    'parse_mode' => 'HTML'
                ]);
            }
        }

    }
}
