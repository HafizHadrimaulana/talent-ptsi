<?php
// config/sitms.php
return [
    'base_url' => rtrim(env('SITMS_BASE_URL', 'https://sitms.ptsi.co.id/admin/api'), '/'),
    'paths'    => [
        'employees_list' => env('SITMS_EMPLOYEE_ENDPOINT', '/employees_list'),
    ],

    // auth
    'api_key'    => env('SITMS_API_KEY', env('SITMS_APIKEY', '')),
    'cookie'     => env('SITMS_COOKIE', ''),
    'auth_mode'  => env('SITMS_AUTH_MODE', 'auto'), // auto|bearer

    // http
    'verify_ssl' => (bool) env('SITMS_VERIFY_SSL', true),
    'timeout'    => (int) env('SITMS_TIMEOUT', 60),
    'retries'    => (int) env('SITMS_RETRIES', 5),

    // behavior
    'pagination' => env('SITMS_PAGINATION', 'page'), // page|datatables
    'per_page'   => (int) env('SITMS_PER_PAGE', 1000),
];
