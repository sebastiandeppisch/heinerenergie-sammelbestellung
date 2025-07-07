<?php

namespace App\Events;

use App\Models\MapPoint;
use App\Models\FormSubmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MapPointCreatedByFormSubmission
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public MapPoint $mapPoint,
        public FormSubmission $submission
    ) {
        //
    }
}
