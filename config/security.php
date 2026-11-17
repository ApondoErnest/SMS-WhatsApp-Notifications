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
        // Host bind (db_backups): write dumps in the folder root unless PATH is set.
        // Local disk: default subdirectory backups/database.
        'path' => env(
            'DB_BACKUP_PATH',
            env('DB_BACKUP_DISK', 'local') === 'db_backups' ? '' : 'backups/database'
        ),
        // Keep this many dump files (older ones are deleted).
        'retain' => (int) env('DB_BACKUP_RETAIN', 14),
    ],
];
