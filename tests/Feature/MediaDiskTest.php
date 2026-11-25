<?php

use Illuminate\Support\Facades\Storage;

afterEach(function (): void {
    unset($_SERVER['MEDIA_DISK_DRIVER'], $_ENV['MEDIA_DISK_DRIVER'], $_SERVER['LARAVEL_CLOUD_DISK_CONFIG'], $_ENV['LARAVEL_CLOUD_DISK_CONFIG']);
});

test('media disks stay local unless switched to s3', function (): void {
    $disks = (require config_path('filesystems.php'))['disks'];

    expect($disks['public']['driver'])->toBe('local')
        ->and($disks['images']['driver'])->toBe('local')
        ->and($disks['image-cache']['driver'])->toBe('local');
});

test('media disks share one bucket with a prefix each when switched to s3, backups stay local', function (): void {
    $_SERVER['MEDIA_DISK_DRIVER'] = $_ENV['MEDIA_DISK_DRIVER'] = 's3';

    $disks = (require config_path('filesystems.php'))['disks'];

    expect($disks['public'])->toMatchArray(['driver' => 's3', 'root' => 'public', 'visibility' => 'public'])
        ->and($disks['images'])->toMatchArray(['driver' => 's3', 'root' => 'images'])
        ->and($disks['image-cache'])->toMatchArray(['driver' => 's3', 'root' => 'image-cache'])
        ->and($disks['local']['driver'])->toBe('local')
        ->and($disks['uploads']['driver'])->toBe('local');
});

test('public files link below /storage of the host locally', function (): void {
    expect(public_storage_url('group-logos/logo.png'))->toBe(asset('storage/group-logos/logo.png'));
});

test('public files link into the bucket on s3', function (): void {
    config(['filesystems.disks.public' => [
        'driver' => 's3',
        'key' => 'key',
        'secret' => 'secret',
        'region' => 'eu-central-1',
        'bucket' => 'media',
        'url' => 'https://media.example.org',
        'root' => 'public',
    ]]);
    Storage::forgetDisk('public');

    expect(public_storage_url('group-logos/logo.png'))->toBe('https://media.example.org/public/group-logos/logo.png');

    Storage::forgetDisk('public');
});

test('media disks use the bucket of Laravel Cloud without further settings', function (): void {
    $_SERVER['MEDIA_DISK_DRIVER'] = $_ENV['MEDIA_DISK_DRIVER'] = '';
    $_SERVER['LARAVEL_CLOUD_DISK_CONFIG'] = $_ENV['LARAVEL_CLOUD_DISK_CONFIG'] = json_encode([[
        'disk' => 'private',
        'is_default' => true,
        'access_key_id' => 'key',
        'access_key_secret' => 'secret',
        'bucket' => 'bucket-uuid',
        'default_region' => 'auto',
        'endpoint' => 'https://account.r2.cloudflarestorage.com',
        'url' => null,
        'use_path_style_endpoint' => false,
    ]]);

    $disks = (require config_path('filesystems.php'))['disks'];

    expect($disks['public'])->toMatchArray([
        'driver' => 's3',
        'key' => 'key',
        'secret' => 'secret',
        'bucket' => 'bucket-uuid',
        'region' => 'auto',
        'endpoint' => 'https://account.r2.cloudflarestorage.com',
        'root' => 'public',
        'url' => null,
    ])
        ->and($disks['public'])->not->toHaveKey('visibility')
        ->and($disks['images'])->toMatchArray(['driver' => 's3', 'bucket' => 'bucket-uuid', 'root' => 'images'])
        ->and($disks['private'])->toMatchArray(['driver' => 's3', 'bucket' => 'bucket-uuid'])
        ->and($disks['private'])->not->toHaveKey('root')
        ->and($disks['local']['driver'])->toBe('local');
});

test('public files of a bucket without address are served by the app', function (): void {
    Storage::fake('public');
    config(['filesystems.disks.public.driver' => 's3', 'filesystems.disks.public.url' => null]);
    Storage::disk('public')->put('characteristics/icon.svg', '<svg/>');

    expect(public_storage_url('characteristics/icon.svg'))->toBe(asset('storage/characteristics/icon.svg'));

    $response = $this->get('/storage/characteristics/icon.svg')->assertOk()->assertCookieMissing(config('session.cookie'));

    expect($response->streamedContent())->toBe('<svg/>')
        ->and($response->headers->get('Cache-Control'))->toContain('public')->toContain('max-age=86400');
    $this->get('/storage/characteristics/missing.svg')->assertNotFound();
    $this->get('/storage/../.env')->assertNotFound();
});
