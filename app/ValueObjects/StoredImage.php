<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * An image already on the image disk, e.g. of a form submission. As the value of an image field it is copied,
 * so the copy has a life cycle of its own.
 */
final readonly class StoredImage
{
    public function __construct(public string $path) {}
}
