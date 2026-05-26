<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationSchedule extends Model
{
    protected $fillable = [
        'center_id',
        'inspection_record_id',
        'channel',
        'scheduled_date',
        'status',
        'attempts',
        'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'last_attempt_at' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(InspectionCenter::class, 'center_id');
    }

    public function inspectionRecord(): BelongsTo
    {
        return $this->belongsTo(InspectionRecord::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }
}
