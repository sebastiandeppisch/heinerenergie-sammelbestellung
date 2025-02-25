<?php

namespace App\Data;

use App\Models\AdviceEvent;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Wnx\Sends\Models\Send;

#[TypeScript]
class AdviceEventData extends Data
{
    public function __construct(
        public int $id,
        public string $description,
        public ?string $comment,
        public string $created_at,
        public ?string $user_name,
        public ?string $initials,
        public string $type, // 'event' or 'mail'
        public ?string $subject = null,
        public ?string $content = null,
        public ?string $to = null,
    ) {}

    public static function fromModel(AdviceEvent $event): self
    {
        $user = $event->user;
        $initials = null;

        if ($user) {
            $first = Str::substr($user->first_name, 0, 1);
            $last = Str::substr($user->last_name, 0, 1);
            $initials = $first && $last ? Str::upper($first.$last) : null;
        }

        return new self(
            id: $event->id,
            description: $event->description,
            comment: $event->comment,
            created_at: $event->created_at->format('Y-m-d H:i:s'),
            user_name: $user?->name,
            initials: $initials,
            type: 'event'
        );
    }

    public static function fromMail(Send $mail): self
    {
        return new self(
            id: $mail->id,
            description: 'E-Mail versendet: '.$mail->subject,
            comment: null,
            created_at: $mail->sent_at?->format('Y-m-d H:i:s') ?? $mail->created_at->format('Y-m-d H:i:s'),
            user_name: null,
            initials: null,
            type: 'mail',
            subject: $mail->subject,
            content: $mail->content,
            to: self::getToFromMail($mail),
        );
    }

    private static function getToFromMail(Send $mail): string
    {
        return implode(',', array_keys($mail->to));
    }
}
