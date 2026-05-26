<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionCenter extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'logo',
        'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'center_id');
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(ImportBatch::class, 'center_id');
    }

    public function inspectionRecords(): HasMany
    {
        return $this->hasMany(InspectionRecord::class, 'center_id');
    }

    public function notificationTemplates(): HasMany
    {
        return $this->hasMany(NotificationTemplate::class, 'center_id');
    }
}
