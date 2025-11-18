<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class FormFieldExistsInRequest implements ValidationRule
{
    /**
     * Get a readable German name for the attribute
     */
    private function getReadableAttributeName(string $attribute): string
    {
        $mapping = [
            'advice_mapping.first_name_field_id' => 'Vorname Feld',
            'advice_mapping.last_name_field_id' => 'Nachname Feld',
            'advice_mapping.address_field_id' => 'Adresse Feld',
            'advice_mapping.email_field_id' => 'E-Mail Feld',
            'advice_mapping.phone_field_id' => 'Telefon Feld',
            'advice_mapping.advice_type_field_id' => 'Beratungstyp Feld',
            'map_point_mapping.title_field_id' => 'Titel Feld',
            'map_point_mapping.description_field_id' => 'Beschreibung Feld',
            'map_point_mapping.coordinate_field_id' => 'Koordinaten Feld',
        ];

        return $mapping[$attribute] ?? $attribute;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return; // Null values are handled by other rules (nullable, required_without, etc.)
        }

        $request = request();
        $fields = $request->input('fields', []);

        if (! is_array($fields)) {
            $fail('Die Felder müssen ein Array sein.');

            return;
        }

        // Extract field IDs from the fields array
        $fieldIds = collect($fields)->pluck('id')->filter()->toArray();

        if (empty($fieldIds)) {
            $fail('Es wurden keine Felder übermittelt.');

            return;
        }

        if (! in_array($value, $fieldIds, true)) {
            $readableName = $this->getReadableAttributeName($attribute);
            $fail("Das {$readableName} existiert nicht in den übermittelten Feldern. Prüfe, ob unter Ziele alle Felder korrekt ausgefüllt sind.");
        }
    }
}
