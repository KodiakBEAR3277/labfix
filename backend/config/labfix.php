<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Seeded superadmin password
    |--------------------------------------------------------------------------
    |
    | The platform superadmin created by DatabaseSeeder is a privileged,
    | platform-wide account, so it must not ship with a known default.
    | When SUPERADMIN_PASSWORD is set the seeder uses it; otherwise the
    | seeder generates a random password and prints it once.
    |
    */
    'superadmin_password' => env('SUPERADMIN_PASSWORD'),
];
