<?php

return [
    'reminder_day_options' => [90, 60, 45, 30, 21, 14, 10, 7, 5, 3, 2, 1],

    // Master switch. When false, due reminders stay pending.
    'sending_enabled' => (bool) env('NOTIFICATIONS_SENDING_ENABLED', false),

    // Local time window (APP_TIMEZONE) in which due reminders are handed to the send queue.
    'send_window_start' => env('NOTIFICATIONS_SEND_WINDOW_START', '08:00'),
    'send_window_end' => env('NOTIFICATIONS_SEND_WINDOW_END', '19:00'),

    // Status keywords (lowercase) that mark an inspection as refused; such records get no reminders.
    'refused_status_keywords' => ['refus', 'reject', 'inapte', 'fail', 'échou', 'echou', 'ajourn'],

    'whatsapp' => [
        // Named body variables for Meta-approved templates ({{customer_name}}, …).
        'template_parameters' => ['customer_name', 'licence_plate', 'expiration_date', 'center_name'],

        // Default Meta template name when a center template has none set.
        'default_template_name' => env('META_WHATSAPP_DEFAULT_TEMPLATE', 'visite_expiry_reminder'),
        'default_language_code' => env('META_WHATSAPP_DEFAULT_LANGUAGE', 'fr'),

        // Estimated Meta conversation rates (USD) for admin cost monitoring.
        // Adjust to Meta's published rates for your country/category.
        'estimated_rates_usd' => [
            'utility' => (float) env('META_WHATSAPP_RATE_UTILITY', 0.004),
            'marketing' => (float) env('META_WHATSAPP_RATE_MARKETING', 0.025),
            'authentication' => (float) env('META_WHATSAPP_RATE_AUTHENTICATION', 0.004),
            'service' => (float) env('META_WHATSAPP_RATE_SERVICE', 0.0),
        ],
    ],

    'sms' => [
        // Phase 6 — Nexah SMS. Keep false until that integration is live.
        'sending_enabled' => (bool) env('SMS_SENDING_ENABLED', false),
    ],
];
