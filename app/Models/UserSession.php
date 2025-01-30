<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    const status = [
        'creating' => 'awaiting_date',
        'reading' => 'reading',
        'deleting' => 'deleting',
        'menu' => 'awaiting_command',
    ];
    protected $table = 'user_sessions';
    protected $guarded = false;
}
