<?php

return [
    'max_file_size_mb' => (int) env('CSV_MAX_FILE_SIZE_MB', 10),
    'max_rows' => (int) env('CSV_MAX_ROWS', 50000),
    'max_error_log_entries' => 1000,
    'default_phone_country' => env('DEFAULT_PHONE_COUNTRY', 'CM'),
    'default_reminder_days' => array_map('intval', explode(',', env('DEFAULT_REMINDER_DAYS', '30,14,7,1'))),
    'csv_delimiter' => ';',
];
