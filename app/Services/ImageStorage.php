<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Laravel\Facades\Image;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stores uploaded images on a disk that is not public. They are served by routes that check access first,
 * because whether an image may be seen can change at any time. Scaled down variants are created on the
 * first request and kept on a disk of their own.
 */
class ImageStorage
{
    public const DISK = 'images';

    public const CACHE_DISK = 'image-cache';

    /** The widths a variant may be requested in, so a client cannot fill the disk with arbitrary sizes. */
    public const WIDTHS = [400, 800];

    private const int MAX_EDGE = 1920;

    /**
     * Scales the image down and drops all metadata, including GPS positions. GD drops metadata anyway, but
     * Imagick keeps it unless it is stripped explicitly. Decoding already corrects the orientation.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $image = Image::decode($file);
        $image->scaleDown(width: self::MAX_EDGE, height: self::MAX_EDGE);

        return $this->put($image, $directory);
    }

    /**
     * Stores an independent copy, so the copy and the original can be deleted on their own.
     */
    public function copy(string $path, string $directory): string
    {
        $copy = $directory.'/'.Str::uuid().'.jpg';
        $this->disk()->copy($path, $copy);

        return $copy;
    }

    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    /**
     * @param  array<int, string>  $paths
     */
    public function delete(array $paths): void
    {
        $this->disk()->delete($paths);

        foreach (self::WIDTHS as $width) {
            $this->cacheDisk()->delete(array_map(fn (string $path): string => $width.'/'.$path, $paths));
        }
    }

    public function deleteDirectory(string $directory): void
    {
        $this->disk()->deleteDirectory($directory);

        foreach (self::WIDTHS as $width) {
            $this->cacheDisk()->deleteDirectory($width.'/'.$directory);
        }
    }

    /**
     * The image, or its variant of the given width. Access must be checked before. The files never change, their
     * names are random, so a client that still has the file gets a 304 without the file being read. Clients may
     * keep files only shortly, because the image may become private.
     */
    public function response(Request $request, string $path, ?int $width = null): Response
    {
        abort_unless($width === null || in_array($width, self::WIDTHS, true), 404);

        $notModified = $this->withCacheHeaders(new Response, $path, $width);

        if ($notModified->isNotModified($request)) {
            return $notModified;
        }

        abort_unless($this->disk()->exists($path), 404);

        $response = $width === null
            ? $this->disk()->response($path)
            : $this->cacheDisk()->response($this->variant($path, $width));

        return $this->withCacheHeaders($response, $path, $width);
    }

    public function etag(string $path, ?int $width = null): string
    {
        return md5($path.'@'.$width);
    }

    private function withCacheHeaders(Response $response, string $path, ?int $width): Response
    {
        $response->setEtag($this->etag($path, $width));
        $response->setPrivate();
        $response->setMaxAge(300);

        return $response;
    }

    /**
     * The variant is written to a temporary file and then moved, so a concurrent request never serves a file
     * that is still being written.
     */
    private function variant(string $path, int $width): string
    {
        $variant = $width.'/'.$path;

        if (! $this->cacheDisk()->exists($variant)) {
            $image = Image::decode($this->disk()->get($path) ?? '');
            $image->scaleDown(width: $width);
            $temporary = $variant.'.'.Str::uuid().'.tmp';
            $this->cacheDisk()->put($temporary, (string) $image->encode(new JpegEncoder(quality: 80, strip: true)));
            $this->cacheDisk()->move($temporary, $variant);
        }

        return $variant;
    }

    private function put(ImageInterface $image, string $directory): string
    {
        $path = $directory.'/'.Str::uuid().'.jpg';
        $this->disk()->put($path, $image->encode(new JpegEncoder(quality: 80, strip: true))->toStream());

        return $path;
    }

    private function disk(): FilesystemAdapter
    {
        return Storage::disk(self::DISK);
    }

    private function cacheDisk(): FilesystemAdapter
    {
        return Storage::disk(self::CACHE_DISK);
    }
}
