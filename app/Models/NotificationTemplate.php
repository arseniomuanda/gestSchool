<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = ['event_key', 'channel', 'locale', 'subject', 'body', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function scopeActive($q)
    {
        return $q->where('active', true);
    }
}
