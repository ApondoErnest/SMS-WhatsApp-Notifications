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
        'disk' => env('DB_BACKUP_DISK', 'local'),
        'path' => env('DB_BACKUP_PATH', 'backups/database'),
        // Keep this many daily backups (older files are deleted).
        'retain' => (int) env('DB_BACKUP_RETAIN', 14),
    ],
];
