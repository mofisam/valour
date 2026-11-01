<?php
return [
    // Neutral fallback only. The real company name is set by the admin in Settings > Business.
    'name'     => env('APP_NAME', 'Laundry Manager'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'timezone' => 'Africa/Lagos',
    'currency' => '₦',
    'per_page' => 15,
    // Sign people out after this many minutes without using the app.
    'idle_minutes' => (int) env('IDLE_MINUTES', 10),
    // Forced base path (leave empty to auto-detect from SCRIPT_NAME)
    'base_path' => env('APP_BASE_PATH', ''),
];
