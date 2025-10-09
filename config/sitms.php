<?php
return [
    'base_url'      => env('SITMS_BASE_URL', 'https://sitms.ptsi.co.id'),
    'apikey'        => env('SITMS_APIKEY', ''),     // API key utama
    'cookie'        => env('SITMS_COOKIE', ''),     // session cookie (opsional)
    'timeout'       => (int) env('SITMS_TIMEOUT', 25),
    'read_enabled'  => filter_var(env('SITMS_READ_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'write_enabled' => filter_var(env('SITMS_WRITE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
];
