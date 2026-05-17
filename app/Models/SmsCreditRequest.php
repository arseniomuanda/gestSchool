<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsCreditRequest extends Model
{
    protected $fillable = [
        'requested_by_user_id',
        'quantity_requested',
        'status',
        'notes',
        'sent_to_ombala_at',
        'processed_at',
        'processed_by_user_id',
    ];

    protected $casts = [
        'sent_to_ombala_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }
}
