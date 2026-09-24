<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\DatabaseDumperContract;
use Closure;

/**
 * Stands in for the MySQL dumper so that the backup routes can be tested on
 * every database the suite runs against, including the in memory SQLite one.
 */
class FakeDatabaseDumper implements DatabaseDumperContract
{
    public const DUMP = "-- fake dump\nSELECT 1;\n";

    public function __construct(private readonly bool $supported = true) {}

    public function supportsCurrentConnection(): bool
    {
        return $this->supported;
    }

    public function dump(Closure $write): void
    {
        $write(self::DUMP);
    }
}
