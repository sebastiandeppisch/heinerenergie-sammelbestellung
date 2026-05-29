<?php

namespace App\Events\Advice;

use App\Models\Advice;
use App\Models\User;
use Override;

class PersonDataChangedEvent extends AdviceEvent
{
    private const array FIELD_LABELS = [
        'first_name' => 'Vorname',
        'last_name' => 'Nachname',
        'email' => 'E-Mail',
        'phone' => 'Telefon',
        'street' => 'Straße',
        'street_number' => 'Hausnummer',
        'zip' => 'PLZ',
        'city' => 'Stadt',
    ];

    /**
     * @param  array<string, array{from: ?string, to: ?string}>  $changes
     */
    public function __construct(
        Advice $advice,
        ?User $user,
        public array $changes
    ) {
        parent::__construct($advice, $user);
    }

    public function getDescription(): string
    {
        $parts = [];

        foreach ($this->changes as $field => $change) {
            $label = self::FIELD_LABELS[$field] ?? $field;
            $from = $change['from'] ?? '';
            $to = $change['to'] ?? '';

            if ($from === '' || $from === null) {
                $parts[] = "{$label} auf '{$to}' gesetzt";
            } elseif ($to === '' || $to === null) {
                $parts[] = "{$label} entfernt (war '{$from}')";
            } else {
                $parts[] = "{$label} von '{$from}' zu '{$to}' geändert";
            }
        }

        return 'Persönliche Daten geändert:'."\n".implode("\n", $parts);
    }

    #[Override]
    public function __serialize(): array
    {
        return ['changes' => $this->changes];
    }

    #[Override]
    public function __unserialize(array $data): void
    {
        $this->changes = $data['changes'];
    }
}
