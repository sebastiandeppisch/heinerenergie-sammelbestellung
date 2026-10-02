<?php

declare(strict_types=1);

return [
    'confirmation' => [
        'expires_days' => (int) env('FORM_CONFIRMATION_EXPIRES_DAYS', 30),

        // Unconfirmed submissions are only deleted when this is set.
        'prune_after_days' => env('FORM_CONFIRMATION_PRUNE_AFTER_DAYS') === null ? null : (int) env('FORM_CONFIRMATION_PRUNE_AFTER_DAYS'),

        'max_per_address_per_hour' => (int) env('FORM_CONFIRMATION_MAX_PER_ADDRESS_PER_HOUR', 3),
        'max_per_ip_per_hour' => (int) env('FORM_CONFIRMATION_MAX_PER_IP_PER_HOUR', 10),
    ],
];
