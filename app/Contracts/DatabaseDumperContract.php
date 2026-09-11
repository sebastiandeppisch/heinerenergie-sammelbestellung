<?php

declare(strict_types=1);

namespace App\Contracts;

use Closure;

interface DatabaseDumperContract
{
    /**
     * Whether the configured database connection can be dumped at all. The
     * application also runs on PostgreSQL and SQLite, for which no dumper
     * exists, so the caller has to be able to ask before offering a backup.
     */
    public function supportsCurrentConnection(): bool;

    /**
     * Writes the whole database as SQL, chunk by chunk, into the given
     * callback. Passing the chunks out instead of writing to a file keeps the
     * dumper free of any knowledge about where the backup ends up - the caller
     * may gzip it, stream it or throw it away.
     *
     * @param  Closure(string): void  $write
     */
    public function dump(Closure $write): void;
}
