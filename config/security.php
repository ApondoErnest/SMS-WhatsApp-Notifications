<?php

return [
    'login' => [
        // Max failed attempts before lockout window.
        'max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        // Lockout decay in seconds.
        'decay_seconds' => (int) env('LOGIN_DECAY_SECONDS', 60),
    ],

    'backups' => [
        'enabled' => (bool) env('DB_BACKUP_ENABLED', true),
        // Production Docker: disk=db_backups (host folder via DB_BACKUP_ROOT).
        // Local/dev default: disk=local under storage/app/private.
        'disk' => env('DB_BACKUP_DISK', 'local'),
        'path' => env('DB_BACKUP_PATH', 'backups/database'),
        // Keep this many dump files (older ones are deleted).
        'retain' => (int) env('DB_BACKUP_RETAIN', 14),
    ],
];
