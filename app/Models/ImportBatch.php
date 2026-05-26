<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'center_id',
        'uploaded_by',
        'filename',
        'original_filename',
        'total_rows',
        'imported_rows',
        'duplicate_rows',
        'failed_rows',
        'status',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(InspectionCenter::class, 'center_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function inspectionRecords(): HasMany
    {
        return $this->hasMany(InspectionRecord::class);
    }

    public function failedRows(): HasMany
    {
        return $this->hasMany(FailedImportRow::class);
    }
}
