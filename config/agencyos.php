<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Public self-registration
    |--------------------------------------------------------------------------
    |
    | AgencyOS is an internal business application. Keep self-registration
    | disabled in production and provision users through an approved process.
    |
    */
    'registration_enabled' => env('AGENCYOS_REGISTRATION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Admin panel access
    |--------------------------------------------------------------------------
    */
    'admin_roles' => array_values(array_filter(array_map(
        static fn (string $role): string => trim($role),
        explode(',', (string) env('AGENCYOS_ADMIN_ROLES', 'Admin,Developer')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Production database policy
    |--------------------------------------------------------------------------
    |
    | MySQL or PostgreSQL is recommended for a multi-user production system.
    | Set this explicitly only for a deliberately single-server SQLite deploy.
    |
    */
    'allow_sqlite_production' => env('AGENCYOS_ALLOW_SQLITE_PRODUCTION', false),
];
