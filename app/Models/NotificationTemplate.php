<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'center_id',
        'channel',
        'language',
        'title',
        'content',
        'meta_template_name',
        'meta_language_code',
        'status',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(InspectionCenter::class, 'center_id');
    }

    public function metaTemplateName(): string
    {
        return (string) ($this->meta_template_name
            ?: config('notifications.whatsapp.default_template_name'));
    }

    public function metaLanguageCode(): string
    {
        if (filled($this->meta_language_code)) {
            return (string) $this->meta_language_code;
        }

        return $this->language === 'en'
            ? 'en'
            : (string) config('notifications.whatsapp.default_language_code', 'fr');
    }
}
