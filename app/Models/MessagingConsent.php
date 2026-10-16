<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessagingConsent extends Model
{
    protected $fillable = [
        'center_id',
        'phone_number',
        'channel',
        'consented',
        'opted_out_at',
        'source',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'consented' => 'boolean',
            'opted_out_at' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(InspectionCenter::class, 'center_id');
    }
}
