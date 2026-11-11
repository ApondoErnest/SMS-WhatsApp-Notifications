<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionCenter extends Model
{
    protected $fillable = [
        'name',
        'code',
        'phone',
        'email',
        'address',
        'city',
        'logo',
        'status',
        'sms_enabled',
        'whatsapp_enabled',
        'reminder_days',
        'notification_language',
    ];

    protected function casts(): array
    {
        return [
            'sms_enabled' => 'boolean',
            'whatsapp_enabled' => 'boolean',
            'reminder_days' => 'array',
        ];
    }

    /**
     * @return list<int>
     */
    public function reminderDays(): array
    {
        $days = $this->reminder_days ?: config('import.default_reminder_days', [30, 14, 7, 1]);

        $days = array_values(array_unique(array_filter(array_map('intval', $days), fn (int $d) => $d > 0)));
        rsort($days);

        return $days;
    }

    /**
     * Preferred customer-facing language for SMS / WhatsApp templates.
     */
    public function notificationLanguage(): string
    {
        return $this->notification_language === 'en' ? 'en' : 'fr';
    }

    /**
     * @return list<string>
     */
    public function enabledChannels(): array
    {
        return array_values(array_filter([
            $this->sms_enabled ? 'sms' : null,
            $this->whatsapp_enabled ? 'whatsapp' : null,
        ]));
    }

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
