<?php

return [
    // Upload / safety caps (hardens for tens–hundreds of thousands of rows).
    'max_file_size_mb' => (int) env('CSV_MAX_FILE_SIZE_MB', 64),
    'max_rows' => (int) env('CSV_MAX_ROWS', 250000),

    // How many validated rows to insert per DB round-trip.
    'insert_chunk_size' => (int) env('CSV_INSERT_CHUNK_SIZE', 500),

    // Persist at most this many failed-row samples (counts still track all failures).
    'max_error_log_entries' => (int) env('CSV_MAX_ERROR_LOG_ENTRIES', 1000),

    // Update import_batches progress every N data rows.
    'progress_every' => (int) env('CSV_PROGRESS_EVERY', 1000),

    // Schedule generation chunk size after import.
    'schedule_chunk_size' => (int) env('CSV_SCHEDULE_CHUNK_SIZE', 200),

    'default_phone_country' => env('DEFAULT_PHONE_COUNTRY', 'CM'),
    'default_reminder_days' => array_map('intval', explode(',', env('DEFAULT_REMINDER_DAYS', '30,14,7,1'))),
    'csv_delimiter' => ';',
];
