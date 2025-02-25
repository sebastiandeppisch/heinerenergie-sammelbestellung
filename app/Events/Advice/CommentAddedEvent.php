<?php

namespace App\Events\Advice;

use App\Models\Advice;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class CommentAddedEvent extends AdviceEvent
{
    use SerializesModels;

    public function __construct(
        public string $comment,
        User $author,
        Advice $advice
    ) {
        parent::__construct($advice, $author);
    }

    public function getDescription(): string
    {
        return "{$this->user->name} hat einen Kommentar hinzugefügt: {$this->comment}";
    }
}
