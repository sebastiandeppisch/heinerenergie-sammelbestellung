<?php

namespace App\ValueObjects;

class Meter implements \Stringable
{
    public function __construct(
        private readonly float $value
    ) {}

    public function getValue(): float
    {
        return $this->value;
    }

    public static function fromValue(float $value): self
    {
        return new self($value);
    }

    public function __toString(): string
    {
        return $this->formatValue($this->value, 'm');
    }

    /**
     * Format a numeric value with appropriate suffix
     */
    private function formatValue(float $n, string $unit, int $significant = 3): string
    {
        $ranges = [
            ['divider' => 1000000000, 'suffix' => 'G'],
            ['divider' => 1000000, 'suffix' => 'M'],
            ['divider' => 1000, 'suffix' => 'k'],
        ];

        foreach ($ranges as $range) {
            if ($n >= $range['divider']) {
                $number = $n / $range['divider'];
                $number = $this->roundSigDigs($number, $significant);

                return ((string) $number).' '.$range['suffix'].$unit;
            }
        }
        $number = $this->roundSigDigs($n, $significant);

        return ((string) $number).' '.$unit;
    }

    /**
     * Round a number to a specific number of significant digits
     */
    private function roundSigDigs(float $number, int $sigdigs): float
    {
        $multiplier = 1;
        while ($number < 0.1) {
            $number *= 10;
            $multiplier /= 10;
        }
        while ($number >= 1) {
            $number /= 10;
            $multiplier *= 10;
        }

        return round($number, $sigdigs) * $multiplier;
    }
}
