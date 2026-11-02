<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | System admin
    |--------------------------------------------------------------------------
    |
    | php artisan db:seed (or migrate:fresh --seed) creates this system admin,
    | with the same fields as the registration. So a test environment can be
    | reset on every deploy and still has the right login. Nothing is created
    | while the email address or password is missing.
    |
    */

    'admin' => [
        'first_name' => env('SEED_ADMIN_FIRST_NAME', 'Admin'),
        'last_name' => env('SEED_ADMIN_LAST_NAME', 'Admin'),
        'email' => env('SEED_ADMIN_EMAIL'),
        'password' => env('SEED_ADMIN_PASSWORD'),
    ],

];
