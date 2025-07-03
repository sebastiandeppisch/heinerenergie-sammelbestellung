<?php

namespace App\ValueObjects;

use App\Casts\Address as AddressCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Stringable;

#[TypeScript]
class Address implements Castable, Stringable
{
    public function __construct(
        public string $street,
        public string $streetNumber,
        public int $zip,
        public string $city
    ) {}

    public function streetWithNumber(): string
    {
        return $this->street.' '.$this->streetNumber;
    }

    public static function castUsing(array $attributes): string
    {
        return AddressCast::class;
    }

    public function hash(): string
    {
        return md5($this->street.$this->streetNumber.$this->zip.$this->city);
    }

    public function __toString(): string
    {
        return $this->streetWithNumber().', '.$this->zip.' '.$this->city;
    }
}
