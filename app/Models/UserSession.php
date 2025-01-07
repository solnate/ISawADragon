<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    const statuses = [
        'create' => 'awaiting_date',
        'delete' => 'deleting',
        'command' => 'awaiting_command',
    ];
    protected $table = 'user_sessions';
    protected $guarded = false;
}
