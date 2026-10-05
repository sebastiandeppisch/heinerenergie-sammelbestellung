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

    /** The heights of thumbnails: twice the 64 and 96 CSS pixels they are shown at, for high density screens. */
    public const HEIGHTS = [128, 192];

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

        foreach ($this->variantDirectories() as $directory) {
            $this->cacheDisk()->delete(array_map(fn (string $path): string => $directory.'/'.$path, $paths));
        }
    }

    public function deleteDirectory(string $directory): void
    {
        $this->disk()->deleteDirectory($directory);

        foreach ($this->variantDirectories() as $variantDirectory) {
            $this->cacheDisk()->deleteDirectory($variantDirectory.'/'.$directory);
        }
    }

    /**
     * The image, or its variant scaled to the given width or height. Access must be checked before. The files
     * never change, their names are random, so a client that still has the file gets a 304 without the file
     * being read. Clients may keep files only shortly, because the image may become private.
     */
    public function response(Request $request, string $path, ?int $width = null, ?int $height = null): Response
    {
        abort_if($width !== null && $height !== null, 404);
        abort_unless($width === null || in_array($width, self::WIDTHS, true), 404);
        abort_unless($height === null || in_array($height, self::HEIGHTS, true), 404);

        $notModified = $this->withCacheHeaders(new Response, $path, $width, $height);

        if ($notModified->isNotModified($request)) {
            return $notModified;
        }

        abort_unless($this->disk()->exists($path), 404);

        $response = $width === null && $height === null
            ? $this->disk()->response($path)
            : $this->cacheDisk()->response($this->variant($path, $width, $height));

        return $this->withCacheHeaders($response, $path, $width, $height);
    }

    public function etag(string $path, ?int $width = null, ?int $height = null): string
    {
        return md5($path.'@'.$width.'x'.$height);
    }

    private function withCacheHeaders(Response $response, string $path, ?int $width, ?int $height): Response
    {
        $response->setEtag($this->etag($path, $width, $height));
        $response->setPrivate();
        $response->setMaxAge(300);

        return $response;
    }

    /**
     * The variant is written to a temporary file and then moved, so a concurrent request never serves a file
     * that is still being written.
     */
    private function variant(string $path, ?int $width, ?int $height): string
    {
        $variant = ($width ?? 'h'.$height).'/'.$path;

        if (! $this->cacheDisk()->exists($variant)) {
            $image = Image::decode($this->disk()->get($path) ?? '');
            $image->scaleDown(width: $width, height: $height);
            $temporary = $variant.'.'.Str::uuid().'.tmp';
            $this->cacheDisk()->put($temporary, (string) $image->encode(new JpegEncoder(quality: 80, strip: true)));
            $this->cacheDisk()->move($temporary, $variant);
        }

        return $variant;
    }

    /**
     * @return array<int, string>
     */
    private function variantDirectories(): array
    {
        return [
            ...array_map(fn (int $width): string => (string) $width, self::WIDTHS),
            ...array_map(fn (int $height): string => 'h'.$height, self::HEIGHTS),
        ];
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
