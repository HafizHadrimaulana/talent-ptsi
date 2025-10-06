<?php

return [

    'models' => [

        'permission' => Spatie\Permission\Models\Permission::class,

        'role' => Spatie\Permission\Models\Role::class,

    ],

    'table_names' => [

        'roles' => 'roles',

        'permissions' => 'permissions',

        'model_has_permissions' => 'model_has_permissions',

        'model_has_roles' => 'model_has_roles',

        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        /*
         * Change this if you want to name the related pivots other than defaults
         */
        'role_pivot_key' => null, // default 'role_id',
        'permission_pivot_key' => null, // default 'permission_id',

        /*
         * Change this if you want to name the related model primary key other than
         * `model_id`.
         */
        'model_morph_key' => 'model_id',

        /*
         * Teams feature: gunakan foreign key selain 'team_id' bila perlu.
         * Kita set ke 'unit_id' agar sesuai unit-scope PTSI.
         */
        'team_foreign_key' => 'unit_id',
    ],

    /*
     * Register the permission check method on the Gate.
     */
    'register_permission_check_method' => true,

    /*
     * Octane/Vapor reset listener (biasanya tidak perlu).
     */
    'register_octane_reset_listener' => false,

    /*
     * Events for attach/detach role/permission.
     */
    'events_enabled' => false,

    /*
     * Teams Feature.
     * SET TRUE untuk mengaktifkan kolom team (di sini: unit_id).
     * Pastikan migrasinya sudah menambahkan kolom terkait.
     */
    'teams' => true,

    /*
     * Team resolver class.
     */
    'team_resolver' => \Spatie\Permission\DefaultTeamResolver::class,

    /*
     * Passport Client Credentials Grant (opsional).
     */
    'use_passport_client_credentials' => false,

    /*
     * Tampilkan nama permission di exception? (disarankan false)
     */
    'display_permission_in_exception' => false,

    /*
     * Tampilkan nama role di exception? (disarankan false)
     */
    'display_role_in_exception' => false,

    /*
     * Wildcard permission (opsional).
     */
    'enable_wildcard_permission' => false,
    // 'wildcard_permission' => Spatie\Permission\WildcardPermission::class,

    /* Cache-specific settings */

    'cache' => [

        /*
         * Lama cache permission.
         */
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),

        /*
         * Cache key utama.
         */
        'key' => 'spatie.permission.cache',

        /*
         * (Opsional) model_key untuk penamaan unik.
         * Biarkan default 'name' agar konsisten.
         */
        'model_key' => 'name',

        /*
         * Store cache (ikuti config cache.php).
         */
        'store' => 'default',
    ],
];
