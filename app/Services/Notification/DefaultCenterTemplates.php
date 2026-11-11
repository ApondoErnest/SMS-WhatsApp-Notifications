<?php

namespace App\Services\Notification;

use App\Models\InspectionCenter;
use App\Models\NotificationTemplate;

class DefaultCenterTemplates
{
    /**
     * Seed the standard SMS/WhatsApp FR+EN templates for a newly created center.
     * Existing templates for the same channel/language are left untouched.
     *
     * @return int Number of templates created
     */
    public function seedFor(InspectionCenter $center): int
    {
        $metaName = (string) config('notifications.whatsapp.default_template_name', 'visite_expiry_reminder');
        $created = 0;

        foreach ($this->definitions($metaName) as $definition) {
            $template = NotificationTemplate::firstOrCreate(
                [
                    'center_id' => $center->id,
                    'channel' => $definition['channel'],
                    'language' => $definition['language'],
                ],
                [
                    'title' => $definition['title'],
                    'content' => $definition['content'],
                    'meta_template_name' => $definition['meta_template_name'],
                    'meta_language_code' => $definition['meta_language_code'],
                    'status' => 'active',
                ]
            );

            if ($template->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * @return list<array{
     *     channel: string,
     *     language: string,
     *     title: string,
     *     content: string,
     *     meta_template_name: ?string,
     *     meta_language_code: ?string
     * }>
     */
    public function definitions(string $metaTemplateName = 'visite_expiry_reminder'): array
    {
        return [
            [
                'channel' => 'sms',
                'language' => 'en',
                'title' => 'Visite Expiry Reminder',
                'content' => 'Dear {customer_name}, the roadworthiness for vehicle {licence_plate} expires on {expiration_date}. Please visit the center for the renewal.',
                'meta_template_name' => null,
                'meta_language_code' => null,
            ],
            [
                'channel' => 'sms',
                'language' => 'fr',
                'title' => 'Rappel visite technique SMS',
                'content' => 'Cher {customer_name}, votre visite technique pour le véhicule {licence_plate} expire le {expiration_date}. Veuillez passer au centre pour le renouvellement.',
                'meta_template_name' => null,
                'meta_language_code' => null,
            ],
            [
                'channel' => 'whatsapp',
                'language' => 'en',
                'title' => 'Visite Expiry Reminder',
                'content' => "Hello {customer_name},\n\nYour roadworthiness inspection for vehicle {licence_plate} expires on {expiration_date}.\nPlease visit {center_name} to renew it.\n\nThank you for your trust.",
                'meta_template_name' => $metaTemplateName,
                'meta_language_code' => 'en',
            ],
            [
                'channel' => 'whatsapp',
                'language' => 'fr',
                'title' => 'Rappel visite technique',
                'content' => "Bonjour {customer_name},\n\nVotre visite technique pour le véhicule {licence_plate} expire le {expiration_date}.\nNous vous invitons à vous rendre à {center_name} pour effectuer votre renouvellement.\n\nMerci de votre confiance.",
                'meta_template_name' => $metaTemplateName,
                'meta_language_code' => 'fr',
            ],
        ];
    }
}
