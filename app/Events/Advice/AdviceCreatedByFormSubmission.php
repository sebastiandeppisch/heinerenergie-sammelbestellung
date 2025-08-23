<?php

namespace App\Events\Advice;

use App\Models\Advice;
use App\Models\FormSubmission;

class AdviceCreatedByFormSubmission extends AdviceEvent
{
    public function __construct(
        public Advice $advice,
        public FormSubmission $submission
    ) {
        parent::__construct($advice);
    }

    public function getDescription(): string
    {
        return 'Beratung ist via Kontaktformular eingegangen '.$this->submission->form_name;
    }
}
