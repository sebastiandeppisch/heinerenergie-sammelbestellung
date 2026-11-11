<?php

use Illuminate\Support\Facades\Storage;

afterEach(function (): void {
    unset($_SERVER['MEDIA_DISK_DRIVER'], $_ENV['MEDIA_DISK_DRIVER']);
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
