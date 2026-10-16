<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationSchedule extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_MISSED = 'missed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_QUEUED,
        self::STATUS_SENT,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
        self::STATUS_MISSED,
    ];

    protected $fillable = [
        'center_id',
        'inspection_record_id',
        'channel',
        'scheduled_date',
        'days_before',
        'status',
        'attempts',
        'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'days_before' => 'integer',
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
