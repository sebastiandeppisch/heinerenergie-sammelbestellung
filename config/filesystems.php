<?php

/**
 * Uploaded media (public files, images and their variants) can live in an S3 bucket, e.g. on staging, with
 * MEDIA_DISK_DRIVER=s3. Each disk gets its own prefix in the bucket. Backups and spreadsheet uploads stay local.
 *
 * On Laravel Cloud the attached bucket comes in LARAVEL_CLOUD_DISK_CONFIG, the media use it without further settings.
 */
$cloudDisks = json_decode((string) env('LARAVEL_CLOUD_DISK_CONFIG', '[]'), true) ?: [];
$cloudBucket = array_values(array_filter($cloudDisks, fn (array $disk): bool => (bool) ($disk['is_default'] ?? false)))[0] ?? $cloudDisks[0] ?? null;

$bucket = $cloudBucket !== null
    ? [
        'key' => $cloudBucket['access_key_id'],
        'secret' => $cloudBucket['access_key_secret'],
        'region' => $cloudBucket['default_region'],
        'bucket' => $cloudBucket['bucket'],
        'endpoint' => $cloudBucket['endpoint'],
        'url' => $cloudBucket['url'] ?? null,
        'use_path_style_endpoint' => $cloudBucket['use_path_style_endpoint'] ?? false,
    ]
    : [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'endpoint' => env('AWS_ENDPOINT'),
        'url' => env('AWS_URL'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    ];

/** Each bucket of Laravel Cloud is a disk of its name, e.g. "private" as FILESYSTEM_DISK. */
$cloudBucketDisks = array_column(array_map(fn (array $disk): array => ['name' => $disk['disk'], 'config' => [
    'driver' => 's3',
    'key' => $disk['access_key_id'],
    'secret' => $disk['access_key_secret'],
    'region' => $disk['default_region'],
    'bucket' => $disk['bucket'],
    'endpoint' => $disk['endpoint'],
    'url' => $disk['url'] ?? null,
    'use_path_style_endpoint' => $disk['use_path_style_endpoint'] ?? false,
    'throw' => true,
]], $cloudDisks), 'config', 'name');

/**
 * @param  array<string, mixed>  $local
 * @param  array<string, mixed>  $s3
 * @return array<string, mixed>
 */
$media = fn (string $prefix, array $local, array $s3 = []): array => env('MEDIA_DISK_DRIVER', $cloudBucket !== null ? 's3' : 'local') === 's3'
    ? [
        'driver' => 's3',
        ...$bucket,
        'url' => null,
        'root' => $prefix,
        'throw' => true,
        ...$s3,
    ]
    : $local;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', env('FILESYSTEM_DRIVER', 'local')),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        // Logos, markers, category images and icons. With a public address of the bucket they are linked there, otherwise
        // the app serves them below /storage, e.g. from the private bucket of Laravel Cloud.
        'public' => $media('public', [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ], array_filter([
            // The prefix "public" is added to this address.
            'url' => $bucket['url'],
            // Buckets of Laravel Cloud (Cloudflare R2) know no object ACLs.
            'visibility' => $cloudBucket === null ? 'public' : null,
        ])),

        // Images of form submissions and map points. Not public: they are served by routes that check access.
        'images' => $media('images', [
            'driver' => 'local',
            'root' => storage_path('app/private/images'),
        ]),

        // Scaled down variants of the images, created when they are requested first.
        'image-cache' => $media('image-cache', [
            'driver' => 'local',
            'root' => storage_path('app/image-cache'),
        ]),

        'uploads' => [
            'driver' => 'local',
            'root' => public_path('/uploads'),
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

        ...$cloudBucketDisks,

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
