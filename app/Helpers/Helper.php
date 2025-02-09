<?php

namespace App\Helpers;

use DateTime;
use Telegram\Bot\Keyboard\Keyboard;

class Helper
{
    public static function getDate(string $message)
    {
        if (preg_match('/\d{1,4}[\/\-.]?\d{1,2}[\/\-.]?\d{1,4}/', $message, $matches)) {
            $rawDate = $matches[0];
            $date = DateTime::createFromFormat('d-m-y', str_replace(['.', '/'], '-', $rawDate));
            if ($date) {
                return [
                    'date' => $date->format('Y-m-d'),
                    'description' => trim(substr($message, strlen($rawDate)))
                ];
            }
        }
        return null;
    }
}
