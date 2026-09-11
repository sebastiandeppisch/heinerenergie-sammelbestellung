<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DatabaseDumperContract;
use App\Exceptions\DatabaseBackupException;
use Closure;
use Illuminate\Database\Connection;
use PDO;
use Pdo\Mysql;

/**
 * Produces a mysqldump compatible SQL dump using only PDO, because the shared
 * hosting the application is deployed to offers neither shell access nor the
 * mysqldump binary.
 *
 * The dump is written in chunks and read with an unbuffered query, so the
 * memory footprint stays flat no matter how large a table is.
 *
 * Only tables and their rows are included. Views, triggers, stored routines
 * and events are left out on purpose: the application defines none of them,
 * and each brings problems of its own, such as views that are bound to the
 * name of the database they were created in.
 */
class MysqlDumper implements DatabaseDumperContract
{
    private const SUPPORTED_DRIVERS = ['mysql', 'mariadb'];

    /**
     * How many rows share one INSERT statement. Fewer, larger statements
     * restore considerably faster than one statement per row.
     */
    private const ROWS_PER_INSERT = 200;

    /**
     * A statement is closed early once its values grow past this, so that no
     * INSERT can run into the max_allowed_packet of the restoring server.
     */
    private const BUFFER_BYTES = 262144;

    public function __construct(private readonly Connection $connection) {}

    public function supportsCurrentConnection(): bool
    {
        return in_array($this->connection->getDriverName(), self::SUPPORTED_DRIVERS, true);
    }

    /**
     * @param  Closure(string): void  $write
     */
    public function dump(Closure $write): void
    {
        if (! $this->supportsCurrentConnection()) {
            throw DatabaseBackupException::unsupportedDriver($this->connection->getDriverName());
        }

        $pdo = $this->connection->getPdo();

        $previousTimeZone = (string) $pdo->query('SELECT @@session.time_zone')->fetchColumn();
        $previousSqlMode = (string) $pdo->query('SELECT @@session.sql_mode')->fetchColumn();

        // Timestamps are converted using the session time zone, so both the
        // read below and the restore later on are pinned to UTC.
        $pdo->exec("SET SESSION time_zone = '+00:00'");

        // The session's sql_mode leaks into the dump. NO_BACKSLASH_ESCAPES
        // makes quote() leave backslashes and newlines raw, which the restore
        // then reads as escape sequences, and ANSI_QUOTES makes SHOW CREATE
        // TABLE quote identifiers in a way the restore reads as strings.
        $pdo->exec("SET SESSION sql_mode = ''");

        // Every table has to be read as of the same point in time. Otherwise a
        // row written between two tables leaves the dump referentially broken,
        // which only shows up when the backup is restored. This gives that
        // snapshot without LOCK TABLES, which shared hosting rarely grants.
        //
        // WITH CONSISTENT SNAPSHOT opens the read view right here rather than
        // at the first SELECT, so nothing that is written in between can slip
        // into the dump. A caller that already opened a transaction brings its
        // own snapshot, and starting one here would commit theirs.
        $ownsTransaction = $this->connection->transactionLevel() === 0;

        if ($ownsTransaction) {
            // The level applies to the next transaction only, so nothing has to
            // be restored afterwards.
            $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
        }

        try {
            $this->writeDump($pdo, $write);
        } finally {
            if ($ownsTransaction) {
                // Read only, so there is nothing worth committing.
                $pdo->exec('ROLLBACK');
            }

            $pdo->exec('SET SESSION sql_mode = '.$pdo->quote($previousSqlMode));
            $pdo->exec('SET SESSION time_zone = '.$pdo->quote($previousTimeZone));
        }
    }

    /**
     * @param  Closure(string): void  $write
     */
    private function writeDump(PDO $pdo, Closure $write): void
    {
        $write($this->header());

        foreach ($this->tables($pdo) as $table) {
            $this->dumpTable($pdo, $write, $table);
        }

        $write($this->footer());
    }

    private function header(): string
    {
        return implode("\n", [
            '-- Dump of '.$this->connection->getDatabaseName().' created '.now()->toIso8601String(),
            'SET NAMES utf8mb4;',
            'SET @OLD_TIME_ZONE=@@TIME_ZONE;',
            // Timestamps are converted using the session time zone on the way
            // out and on the way back in. Pinning both ends to UTC keeps them
            // from shifting when the dump is restored on another server.
            "SET TIME_ZONE='+00:00';",
            'SET @OLD_SQL_MODE=@@SQL_MODE;',
            "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';",
            'SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;',
            'SET FOREIGN_KEY_CHECKS=0;',
            '', '',
        ]);
    }

    private function footer(): string
    {
        return implode("\n", [
            'SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;',
            'SET SQL_MODE=@OLD_SQL_MODE;',
            'SET TIME_ZONE=@OLD_TIME_ZONE;',
            '',
        ]);
    }

    /**
     * Everything but views, so that no table type the dumper does not know
     * about silently goes missing from the backup.
     *
     * @return list<string>
     */
    private function tables(PDO $pdo): array
    {
        $tables = [];

        foreach ($pdo->query("SHOW FULL TABLES WHERE Table_type <> 'VIEW'", PDO::FETCH_NUM) as $row) {
            $tables[] = (string) $row[0];
        }

        return $tables;
    }

    /**
     * @param  Closure(string): void  $write
     */
    private function dumpTable(PDO $pdo, Closure $write, string $table): void
    {
        $quoted = $this->quoteIdentifier($table);
        $schema = (string) $pdo->query('SHOW CREATE TABLE '.$quoted)->fetch(PDO::FETCH_NUM)[1];

        $write('DROP TABLE IF EXISTS '.$quoted.";\n".$schema.";\n\n");

        $columns = $this->writableColumns($pdo, $quoted);

        if ($columns === []) {
            return;
        }

        $columnList = implode(',', array_map(
            fn (array $column): string => $this->quoteIdentifier($column['name']),
            $columns
        ));
        $selectList = implode(',', array_column($columns, 'select'));
        $insertInto = 'INSERT INTO '.$quoted.' ('.$columnList.') VALUES ';

        // Unbuffered, otherwise PDO pulls the entire table into memory before
        // the loop below sees its first row.
        $statement = $pdo->prepare(
            'SELECT '.$selectList.' FROM '.$quoted,
            [Mysql::ATTR_USE_BUFFERED_QUERY => false]
        );
        $statement->execute();

        try {
            $values = [];
            $buffered = 0;

            while ($row = $statement->fetch(PDO::FETCH_NUM)) {
                $tuple = '('.implode(',', array_map(fn (mixed $value): string => $this->formatValue($pdo, $value), $row)).')';

                $values[] = $tuple;
                $buffered += strlen($tuple);

                if (count($values) >= self::ROWS_PER_INSERT || $buffered > self::BUFFER_BYTES) {
                    $write($insertInto.implode(',', $values).";\n");
                    $values = [];
                    $buffered = 0;
                }
            }

            if ($values !== []) {
                $write($insertInto.implode(',', $values).";\n");
            }
        } finally {
            // Releases the unbuffered result right here when a write fails
            // halfway through the table, instead of whenever the statement
            // happens to be collected. Either way the rest of the result is
            // read off the wire first, so this is about clarity, not speed.
            $statement->closeCursor();
        }

        $write("\n");
    }

    /**
     * Generated columns are skipped, because MySQL refuses an INSERT that
     * supplies a value for them. Naming the remaining columns explicitly also
     * lets the dump restore into a schema whose column order has changed.
     *
     * @return list<array{name: string, select: string}>
     */
    private function writableColumns(PDO $pdo, string $quotedTable): array
    {
        $columns = [];

        foreach ($pdo->query('SHOW COLUMNS FROM '.$quotedTable, PDO::FETCH_ASSOC) as $column) {
            // Only real generated columns. MySQL 8 also reports a plain
            // DEFAULT CURRENT_TIMESTAMP as DEFAULT_GENERATED, and skipping
            // those would fill them with the time of the restore.
            if (preg_match('/\b(VIRTUAL|STORED|PERSISTENT) GENERATED\b/i', (string) ($column['Extra'] ?? '')) === 1) {
                continue;
            }

            $quoted = $this->quoteIdentifier((string) $column['Field']);

            $columns[] = [
                'name' => (string) $column['Field'],
                // Let the server render approximate numbers. Passing them
                // through a PHP float would round them to the
                // serialize_precision ini setting, which a shared host is free
                // to lower. The cast matters for FLOAT, which MySQL renders
                // with six significant digits that do not read back as the
                // same value, while its exact DOUBLE form does.
                'select' => $this->isApproximateNumber((string) $column['Type'])
                    ? 'CONCAT(CAST('.$quoted.' AS DOUBLE)) AS '.$quoted
                    : $quoted,
            ];
        }

        return $columns;
    }

    /**
     * FLOAT, DOUBLE and REAL are binary approximations whose text form depends
     * on whoever renders it. DECIMAL is exact and already arrives as a string.
     */
    private function isApproximateNumber(string $type): bool
    {
        $name = strtolower((string) preg_replace('/[( ].*$/s', '', $type));

        return in_array($name, ['float', 'double', 'real'], true);
    }

    private function quoteIdentifier(string $name): string
    {
        return '`'.str_replace('`', '``', $name).'`';
    }

    /**
     * Values that are not valid UTF-8 are written as hex literals, everything
     * else stays readable so the dump can be inspected and edited by hand.
     */
    private function formatValue(PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            // A safety net: approximate columns are rendered by the server, so
            // nothing should arrive here as a float. Should a type slip past
            // that, 17 significant digits still read back as the same double.
            // Unlike %G, %H ignores the locale's decimal separator.
            return sprintf('%.17H', $value);
        }

        $value = (string) $value;

        if ($value === '') {
            return "''";
        }

        if (! mb_check_encoding($value, 'UTF-8')) {
            return '0x'.bin2hex($value);
        }

        return $pdo->quote($value);
    }
}
