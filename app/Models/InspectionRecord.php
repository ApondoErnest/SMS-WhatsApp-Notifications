<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionRecord extends Model
{
    protected $fillable = [
        'center_id',
        'import_batch_id',
        'registration_date',
        'inspection_date',
        'expiration_date',
        'vehicle_class',
        'inspection_type',
        'licence_plate',
        'vehicle_category',
        'customer_name',
        'phone_number',
        'normalized_phone_number',
        'status',
        'record_hash',
    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
            'inspection_date' => 'date',
            'expiration_date' => 'date',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(InspectionCenter::class, 'center_id');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function notificationSchedules(): HasMany
    {
        return $this->hasMany(NotificationSchedule::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }
}
