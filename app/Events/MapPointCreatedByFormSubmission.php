<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\FormSubmission;
use App\Models\MapPoint;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MapPointCreatedByFormSubmission
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  string|null  $editToken  the token of the manage link, issued once for the mail and the page after submitting
     */
    public function __construct(
        public MapPoint $mapPoint,
        public FormSubmission $submission,
        public ?string $editToken = null,
    ) {}
}
