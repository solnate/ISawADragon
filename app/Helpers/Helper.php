<?php

namespace App\Helpers;

use Telegram\Bot\Keyboard\Keyboard;

class Helper
{
    public static function getDate(string $message)
    {
        preg_match('/\d{1,4}[\/\-\.]?\d{1,2}[\/\-\.]?\d{1,4}/', $message, $matches);
        if (isset($matches[0])) {
            $date = $matches[0]; // Дата
            $description = substr($message, strlen($date) + 1);
            return ['date' => $date, 'description' => $description];
        } else {
            return null;
        }
    }
}
