<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
     * The OpenStreetMap usage policy caps automated clients at one request per
     * second and requires a real contact address in the user agent. We stay at
     * one request every two seconds and refuse to queue up beyond max_wait.
     */
    'nominatim' => [
        'url' => env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org/'),
        'contact' => env('NOMINATIM_CONTACT', env('ADMIN_EMAIL')),
        'interval' => (float) env('NOMINATIM_INTERVAL', 2.0),
        'max_wait' => (float) env('NOMINATIM_MAX_WAIT', 10.0),
        'connect_timeout' => (float) env('NOMINATIM_CONNECT_TIMEOUT', 2.0),
        'timeout' => (float) env('NOMINATIM_TIMEOUT', 5.0),
    ],

];
