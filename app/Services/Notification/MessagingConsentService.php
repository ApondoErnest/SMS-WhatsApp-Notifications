<?php

namespace App\Services\Notification;

use App\Models\MessagingConsent;
use Illuminate\Support\Carbon;

class MessagingConsentService
{
    public function isAllowed(int $centerId, string $phoneE164, string $channel = 'whatsapp'): bool
    {
        $consent = MessagingConsent::query()
            ->where('center_id', $centerId)
            ->where('phone_number', $phoneE164)
            ->where('channel', $channel)
            ->first();

        // No consent row yet: treat as allowed for business-initiated reminders after CSV import
        // (phone was collected for renewal contact). Explicit opt-out always wins.
        if ($consent === null) {
            return true;
        }

        return $consent->consented && $consent->opted_out_at === null;
    }

    public function grant(int $centerId, string $phoneE164, string $channel = 'whatsapp', string $source = 'import'): MessagingConsent
    {
        return MessagingConsent::updateOrCreate(
            [
                'center_id' => $centerId,
                'phone_number' => $phoneE164,
                'channel' => $channel,
            ],
            [
                'consented' => true,
                'opted_out_at' => null,
                'source' => $source,
                'notes' => null,
            ]
        );
    }

    public function revoke(int $centerId, string $phoneE164, string $channel = 'whatsapp', string $source = 'opt_out', ?string $notes = null): MessagingConsent
    {
        return MessagingConsent::updateOrCreate(
            [
                'center_id' => $centerId,
                'phone_number' => $phoneE164,
                'channel' => $channel,
            ],
            [
                'consented' => false,
                'opted_out_at' => Carbon::now(),
                'source' => $source,
                'notes' => $notes,
            ]
        );
    }

    /**
     * Grant WhatsApp consent for a newly imported phone unless the customer already opted out.
     */
    public function ensureImportConsent(int $centerId, string $phoneE164): void
    {
        $existing = MessagingConsent::query()
            ->where('center_id', $centerId)
            ->where('phone_number', $phoneE164)
            ->where('channel', 'whatsapp')
            ->first();

        if ($existing && (! $existing->consented || $existing->opted_out_at !== null)) {
            return;
        }

        $this->grant($centerId, $phoneE164, 'whatsapp', 'import');
    }
}
