<?php

declare(strict_types=1);

return [
    'confirmation_expires_hours' => (int) env('MAP_CONTACT_CONFIRMATION_EXPIRES_HOURS', 24),

    'message_max_length' => 2000,

    'limits' => [
        'per_ip_per_hour' => (int) env('MAP_CONTACT_LIMIT_PER_IP_PER_HOUR', 5),
        'per_sender_per_day' => (int) env('MAP_CONTACT_LIMIT_PER_SENDER_PER_DAY', 3),
        'per_point_per_week' => (int) env('MAP_CONTACT_LIMIT_PER_POINT_PER_WEEK', 10),
    ],

    'retention' => [
        'confirmed_days' => (int) env('MAP_CONTACT_RETENTION_CONFIRMED_DAYS', 90),
        'unconfirmed_days' => (int) env('MAP_CONTACT_RETENTION_UNCONFIRMED_DAYS', 7),
    ],
];
