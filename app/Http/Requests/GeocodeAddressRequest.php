<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\ValueObjects\Address;
use Illuminate\Foundation\Http\FormRequest;

class GeocodeAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'street' => ['nullable', 'string', 'max:255'],
            'street_number' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function address(): Address
    {
        return new Address(
            street: $this->string('street')->toString(),
            street_number: $this->string('street_number')->toString(),
            zip: $this->string('zip')->toString(),
            city: $this->string('city')->toString(),
        );
    }
}
