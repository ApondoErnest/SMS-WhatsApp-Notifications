<?php

namespace App\Services\Notification;

use App\Models\InspectionRecord;
use App\Models\NotificationTemplate;
use Carbon\Carbon;

class MessagePersonalizer
{
    /**
     * @return array{customer_name: string, licence_plate: string, expiration_date: string, center_name: string}
     */
    public function variables(InspectionRecord $record): array
    {
        $record->loadMissing('center');

        return [
            'customer_name' => (string) $record->customer_name,
            // WhatsApp templates: plate without spaces/dashes (e.g. LT164HP).
            'licence_plate' => self::compactLicencePlate((string) $record->licence_plate),
            'expiration_date' => Carbon::parse($record->expiration_date)->format('d/m/Y'),
            'center_name' => (string) ($record->center?->name ?? ''),
        ];
    }

    public static function compactLicencePlate(string $plate): string
    {
        return mb_strtoupper(preg_replace('/[\s\-]+/', '', $plate) ?? $plate);
    }

    /**
     * Named body parameters for Meta templates, e.g. {{customer_name}}.
     *
     * @return list<array{name: string, text: string}>
     */
    public function metaBodyParameters(InspectionRecord $record): array
    {
        $vars = $this->variables($record);
        $order = config('notifications.whatsapp.template_parameters', [
            'customer_name',
            'licence_plate',
            'expiration_date',
            'center_name',
        ]);

        return array_map(
            fn (string $key) => [
                'name' => $key,
                'text' => $vars[$key] ?? '',
            ],
            $order
        );
    }

    public function render(string $content, InspectionRecord $record): string
    {
        $vars = $this->variables($record);
        $replacements = [];

        foreach ($vars as $key => $value) {
            $replacements['{'.$key.'}'] = $value;
        }

        return strtr($content, $replacements);
    }

    public function resolveWhatsAppTemplate(int $centerId, string $preferredLanguage = 'fr'): ?NotificationTemplate
    {
        return $this->resolveTemplate($centerId, 'whatsapp', $preferredLanguage);
    }

    public function resolveSmsTemplate(int $centerId, string $preferredLanguage = 'fr'): ?NotificationTemplate
    {
        return $this->resolveTemplate($centerId, 'sms', $preferredLanguage);
    }

    private function resolveTemplate(int $centerId, string $channel, string $preferredLanguage = 'fr'): ?NotificationTemplate
    {
        $query = NotificationTemplate::query()
            ->where('center_id', $centerId)
            ->where('channel', $channel)
            ->where('status', 'active');

        $template = (clone $query)->where('language', $preferredLanguage)->first();

        return $template ?? $query->orderBy('language')->first();
    }
}
