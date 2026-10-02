<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Something a form target wants to tell the submitter once it ran, e.g. a link to manage the created item.
 */
#[TypeScript]
class FormTargetNoticeData extends Data
{
    public function __construct(
        public string $title,
        public ?string $text = null,
        public ?string $url = null,
        public ?string $url_label = null,
    ) {}
}
