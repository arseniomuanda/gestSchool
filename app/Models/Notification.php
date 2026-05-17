<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'recipient_user_id',
        'recipient_address',
        'channel',
        'event_key',
        'status',
        'subject',
        'body_preview',
        'sent_at',
        'error',
        'payload',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'payload' => 'array',
    ];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
