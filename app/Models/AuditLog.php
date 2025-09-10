<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'action',
        'system',
        'user_id',
        'user_type',
        'streamed_at',
        'data',
        'level',
        'trace_id',
        'session_id',
        'message',
    ];
}
