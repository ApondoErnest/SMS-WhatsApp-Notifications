<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'center_id',
        'inspection_record_id',
        'notification_schedule_id',
        'channel',
        'provider',
        'phone_number',
        'message',
        'provider_message_id',
        'delivery_status',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
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

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(NotificationSchedule::class, 'notification_schedule_id');
    }
}
