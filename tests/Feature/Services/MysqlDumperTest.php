<?php

declare(strict_types=1);

use App\Exceptions\DatabaseBackupException;
use App\Services\MysqlDumper;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

/**
 * A dump can only be proven by restoring it, and a restore drops and recreates
 * every table. These tests therefore work on their own scratch database with a
 * purpose built schema, so that neither the test database nor the application
 * schema is involved.
 */
beforeEach(function (): void {
    // The connection may come from DB_URL, so the resolved driver is what
    // counts, not the name of the configured connection.
    if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
        test()->markTestSkipped('The dumper only supports MySQL and MariaDB.');
    }

    $this->scratch = scratchDatabaseName();

    try {
        serverConnection()->statement('CREATE DATABASE `'.$this->scratch.'`');
    } catch (Throwable $e) {
        test()->markTestSkipped('The test database user may not create databases: '.$e->getMessage());
    }
});

afterEach(function (): void {
    if (isset($this->scratch)) {
        serverConnection()->statement('DROP DATABASE IF EXISTS `'.$this->scratch.'`');
    }

    DB::purge('dumper_server');
    DB::purge('dumper_scratch');
    DB::purge('dumper_restore');
    DB::purge('dumper_writer');
});

function scratchDatabaseName(): string
{
    return 'dumper_test_'.getmypid();
}

/**
 * Connected to the server but to no database, for creating and dropping the
 * scratch database.
 */
function serverConnection(): Connection
{
    $config = DB::connection()->getConfig();
    $config['url'] = null;
    $config['database'] = null;
    config(['database.connections.dumper_server' => $config]);

    return DB::connection('dumper_server');
}

function scratchConnection(string $name = 'dumper_scratch'): Connection
{
    $config = DB::connection()->getConfig();
    $config['url'] = null;
    $config['database'] = scratchDatabaseName();
    config(['database.connections.'.$name => $config]);

    return DB::connection($name);
}

function dumpScratch(Connection $connection): string
{
    $sql = '';

    (new MysqlDumper($connection))->dump(function (string $chunk) use (&$sql): void {
        $sql .= $chunk;
    });

    return $sql;
}

/**
 * Plays the dump back the way phpMyAdmin does on the shared hosting: statement
 * by statement, on a connection of its own.
 *
 * The dumper escapes newlines inside values, so a semicolon at the end of a
 * line always ends a statement. The "statement end" case of the round trip
 * above is what guards that assumption.
 */
function restoreScratch(string $sql, ?string $timeZone = null): PDO
{
    $config = DB::connection()->getConfig();

    $pdo = new PDO(
        'mysql:host='.$config['host'].';port='.($config['port'] ?? 3306).';dbname='.scratchDatabaseName().';charset=utf8mb4',
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );

    if ($timeZone !== null) {
        $pdo->exec('SET SESSION time_zone = '.$pdo->quote($timeZone));
    }

    foreach (explode(";\n", $sql) as $statement) {
        if (trim($statement) !== '') {
            $pdo->exec($statement);
        }
    }

    return $pdo;
}

/**
 * A connection to the scratch database that has not seen the restore, so an
 * assertion reads what really ended up on disk.
 */
function restoredConnection(): Connection
{
    return scratchConnection('dumper_restore');
}

/**
 * Column types the schema uses or could easily start using, with the values
 * that tend to break a dump: the largest unsigned BIGINT, BIT values on both
 * sides of valid UTF-8, geometry, a JSON polygon, JSON with quotes and
 * backslashes, fractional seconds, binary ids that happen to be valid UTF-8
 * and columns with a collation of their own.
 */
function createColumnTypesTable(Connection $connection): void
{
    $connection->unprepared(<<<'SQL'
        CREATE TABLE column_types (
          id INT UNSIGNED PRIMARY KEY,
          flag TINYINT(1) NOT NULL,
          big BIGINT UNSIGNED NOT NULL,
          bits BIT(8) NULL,
          kind ENUM('a','b','c') NULL,
          tags SET('x','y','z') NULL,
          born YEAR NULL,
          day DATE NULL,
          clock TIME(6) NULL,
          happened DATETIME(6) NULL,
          doc JSON NULL,
          location POINT NULL,
          area POLYGON NULL,
          raw_id BINARY(16) NULL,
          payload LONGBLOB NULL,
          exact VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
          legacy VARCHAR(50) CHARACTER SET latin1 COLLATE latin1_german1_ci NULL
        ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

    $connection->unprepared(<<<'SQL'
        INSERT INTO column_types VALUES
        (1, 1, 18446744073709551615, b'10100101', 'b', 'x,z', 2026, '2026-09-10', '23:59:59.123456', '2026-09-10 12:34:56.789012',
         '{"type": "Polygon", "coordinates": [[[8.6510204, 49.8728475], [8.66, 49.88], [8.65, 49.89], [8.6510204, 49.8728475]]]}',
         ST_GeomFromText('POINT(8.6510204 49.8728475)'),
         ST_GeomFromText('POLYGON((8.65 49.87, 8.66 49.88, 8.65 49.89, 8.65 49.87))'),
         UNHEX('00112233445566778899AABBCCDDEEFF'), UNHEX('00FF00FE0A0D5C27'), 'Grüße 😀', 'Müller'),
        (2, 0, 0, b'00000000', NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
        (3, 1, 42, b'00000001', 'a', 'y', 1901, '1000-01-01', '-12:00:00', '9999-12-31 23:59:59.999999',
         '{"quote": "O\'Reilly", "backslash": "a\\\\b", "unicode": "中文"}',
         NULL, NULL, UNHEX('41414141414141414141414141414141'), 'plain text that is valid utf8', 'Case', 'Ärger')
        SQL);
}

/**
 * Every column rendered as text by the server, so a comparison sees the
 * stored bytes rather than whatever PHP makes of them.
 *
 * @return array<int, array<string, mixed>>
 */
function columnTypesAsText(Connection $connection): array
{
    return array_map(fn (object $row): array => (array) $row, $connection->select(<<<'SQL'
        SELECT id, flag, CAST(big AS CHAR) AS big, HEX(bits) AS bits, kind, tags, born, CAST(day AS CHAR) AS day,
               CAST(clock AS CHAR) AS clock, CAST(happened AS CHAR) AS happened, CAST(doc AS CHAR) AS doc,
               ST_AsText(location) AS location, ST_SRID(location) AS srid, ST_AsText(area) AS area,
               HEX(raw_id) AS raw_id, HEX(payload) AS payload, HEX(exact) AS exact, HEX(legacy) AS legacy
        FROM column_types ORDER BY id
        SQL));
}

it('brings back rows that were changed after the dump was taken', function (): void {
    $connection = scratchConnection();
    $connection->statement('CREATE TABLE notes (id INT PRIMARY KEY, body TEXT)');
    $connection->table('notes')->insert(['id' => 1, 'body' => 'Ursprünglich']);

    $sql = dumpScratch($connection);

    $connection->table('notes')->where('id', 1)->update(['body' => 'Später überschrieben']);
    $connection->table('notes')->insert(['id' => 2, 'body' => 'Nach dem Backup angelegt']);

    restoreScratch($sql);
    $restored = restoredConnection();

    expect($restored->table('notes')->count())->toBe(1)
        ->and($restored->table('notes')->where('id', 1)->value('body'))->toBe('Ursprünglich');
});

it('survives values that would break a naive dump', function (string $value): void {
    $connection = scratchConnection();
    $connection->statement('CREATE TABLE notes (id INT PRIMARY KEY, body TEXT)');
    $connection->table('notes')->insert(['id' => 1, 'body' => $value]);

    restoreScratch(dumpScratch($connection));

    expect(restoredConnection()->table('notes')->where('id', 1)->value('body'))->toBe($value);
})->with([
    'quote' => "O'Reilly",
    'backslash' => 'C:\\Users\\backslash',
    'statement end' => "ende;\nINSERT INTO notes VALUES (99, 'injected');",
    'unicode' => 'Grüße 😀 中文',
    'null byte' => "vor\0nach",
    'empty' => '',
]);

it('keeps binary data that is not valid UTF-8', function (): void {
    $binary = "\x00\x01\xC3\x28\xFF\xFE".random_bytes(64);

    $connection = scratchConnection();
    $connection->statement('CREATE TABLE blobs (id INT PRIMARY KEY, payload VARBINARY(255))');
    $connection->table('blobs')->insert(['id' => 1, 'payload' => $binary]);

    $sql = dumpScratch($connection);

    // MySQL would take the raw bytes back either way, but the file itself has
    // to stay valid UTF-8. phpMyAdmin or an editor the backup passes through
    // may otherwise mangle them before they ever reach the server.
    expect(mb_check_encoding($sql, 'UTF-8'))->toBeTrue();

    restoreScratch($sql);

    expect(restoredConnection()->table('blobs')->where('id', 1)->value('payload'))->toBe($binary);
});

it('restores a table that has a generated column', function (): void {
    $connection = scratchConnection();
    $connection->statement('CREATE TABLE prices (
        id INT PRIMARY KEY,
        net INT NOT NULL,
        tax INT AS (net * 19 / 100) STORED
    )');
    $connection->table('prices')->insert(['id' => 1, 'net' => 100]);

    // MySQL rejects an INSERT that supplies a value for a generated column, so
    // the dump has to name the columns it writes.
    restoreScratch(dumpScratch($connection));
    $restored = restoredConnection();

    expect((int) $restored->table('prices')->where('id', 1)->value('net'))->toBe(100)
        ->and((int) $restored->table('prices')->where('id', 1)->value('tax'))->toBe(19);
});

it('keeps columns whose default is an expression', function (): void {
    $connection = scratchConnection();
    // MySQL 8 reports these as DEFAULT_GENERATED although they hold ordinary
    // data. useCurrent() and useCurrentOnUpdate() produce exactly this.
    $connection->statement('CREATE TABLE jobs (
        id INT PRIMARY KEY,
        failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        touched_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )');
    $connection->statement("INSERT INTO jobs VALUES (1, '2020-01-02 03:04:05', '2021-06-07 08:09:10')");

    restoreScratch(dumpScratch($connection));

    $row = restoredConnection()->selectOne('SELECT CAST(failed_at AS CHAR) AS failed_at, CAST(touched_at AS CHAR) AS touched_at FROM jobs WHERE id = 1');

    expect($row->failed_at)->toBe('2020-01-02 03:04:05')
        ->and($row->touched_at)->toBe('2021-06-07 08:09:10');
});

it('writes a restorable dump even when the session escapes nothing', function (): void {
    $connection = scratchConnection();
    $connection->statement('CREATE TABLE notes (id INT PRIMARY KEY, body TEXT, doc JSON)');

    // Some hosts run with these. NO_BACKSLASH_ESCAPES stops quote() from
    // escaping backslashes and newlines, ANSI_QUOTES changes how SHOW CREATE
    // TABLE quotes identifiers. The dump must inherit neither.
    $connection->statement("SET SESSION sql_mode = 'NO_BACKSLASH_ESCAPES,ANSI_QUOTES'");
    $connection->table('notes')->insert(['id' => 1, 'body' => "C:\\temp\nzeile", 'doc' => '{"say": "\\"hi\\""}']);

    $sql = dumpScratch($connection);

    // The session is handed back the way the dumper found it.
    expect($connection->scalar('SELECT @@session.sql_mode'))->toBe('ANSI_QUOTES,NO_BACKSLASH_ESCAPES');

    restoreScratch($sql);
    $row = restoredConnection()->selectOne('SELECT body, CAST(doc AS CHAR) AS doc FROM notes WHERE id = 1');

    expect($row->body)->toBe("C:\\temp\nzeile")
        ->and(json_decode($row->doc, true))->toBe(['say' => '"hi"']);
});

it('does not shift timestamps when the dump is restored in another time zone', function (): void {
    $connection = scratchConnection();
    $connection->statement("SET SESSION time_zone = '+02:00'");
    $connection->statement('CREATE TABLE events (id INT PRIMARY KEY, happened_at TIMESTAMP NOT NULL)');
    $connection->statement("INSERT INTO events VALUES (1, '2026-09-08 12:00:00')");

    $instant = (int) $connection->selectOne('SELECT UNIX_TIMESTAMP(happened_at) AS ts FROM events WHERE id = 1')->ts;

    $sql = dumpScratch($connection);

    // The machine a backup is restored on rarely runs in the same time zone.
    $restored = restoreScratch($sql, timeZone: '+05:00');

    expect((int) $restored->query('SELECT UNIX_TIMESTAMP(happened_at) AS ts FROM events WHERE id = 1')->fetchColumn())
        ->toBe($instant);
});

it('restores numbers without losing digits', function (): void {
    $connection = scratchConnection();
    $connection->statement('CREATE TABLE numbers (id INT PRIMARY KEY, lat DECIMAL(10,7), amount DOUBLE, ratio FLOAT)');
    // Neither 1/3 nor 16777217 fits a FLOAT exactly. MySQL renders them with
    // six significant digits, which read back as different values.
    $connection->statement("INSERT INTO numbers VALUES (1, '49.8728475', 0.1234567890123456, 1/3), (2, NULL, NULL, 16777217)");

    $exactly = fn (Connection $on): array => $on->table('numbers')
        ->selectRaw('CAST(lat AS CHAR) AS lat, CAST(amount AS CHAR) AS amount, CAST(CAST(ratio AS DOUBLE) AS CHAR) AS ratio')
        ->orderBy('id')
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();

    $original = $exactly($connection);

    // A shared host is free to lower this. Rendering the value in PHP would
    // then silently drop digits, so the server has to render it instead.
    $previous = ini_set('serialize_precision', '14');

    try {
        restoreScratch(dumpScratch($connection));
    } finally {
        ini_set('serialize_precision', $previous === false ? '-1' : $previous);
    }

    expect($exactly(restoredConnection()))->toBe($original);
});

it('restores every column type with its edge values', function (): void {
    $connection = scratchConnection();
    createColumnTypesTable($connection);
    $original = columnTypesAsText($connection);

    restoreScratch(dumpScratch($connection));

    expect(columnTypesAsText(restoredConnection()))->toBe($original);
});

it('keeps the collations when the restoring server defaults to another one', function (): void {
    $connection = scratchConnection();
    createColumnTypesTable($connection);

    restoreScratch(dumpScratch($connection));

    // The scratch database is created with the server default, so only the
    // dump itself can have carried these over.
    $restored = restoredConnection();

    $tableCollation = $restored->table('information_schema.TABLES')
        ->whereRaw('TABLE_SCHEMA = DATABASE()')
        ->where('TABLE_NAME', 'column_types')
        ->value('TABLE_COLLATION');

    $columnCollations = $restored->table('information_schema.COLUMNS')
        ->whereRaw('TABLE_SCHEMA = DATABASE()')
        ->where('TABLE_NAME', 'column_types')
        ->whereNotNull('COLLATION_NAME')
        ->pluck('COLLATION_NAME', 'COLUMN_NAME')
        ->all();

    expect($tableCollation)->toBe('utf8mb4_unicode_ci')
        ->and($columnCollations)->toMatchArray([
            'kind' => 'utf8mb4_unicode_ci',
            'exact' => 'utf8mb4_bin',
            'legacy' => 'latin1_german1_ci',
        ]);
});

it('reads every table from the same snapshot', function (): void {
    $connection = scratchConnection();
    // order_lines sorts before orders, so a write between the two tables of the
    // dump can produce an order whose lines were never read.
    $connection->statement('CREATE TABLE order_lines (id INT PRIMARY KEY, order_id INT) ENGINE=InnoDB');
    $connection->statement('CREATE TABLE orders (id INT PRIMARY KEY) ENGINE=InnoDB');
    $connection->table('orders')->insert(['id' => 1]);
    $connection->table('order_lines')->insert(['id' => 1, 'order_id' => 1]);

    $writer = scratchConnection('dumper_writer');
    $sql = '';

    (new MysqlDumper($connection))->dump(function (string $chunk) use (&$sql, $writer): void {
        $sql .= $chunk;

        if (str_contains($chunk, 'INSERT INTO `order_lines`')) {
            $writer->table('orders')->insert(['id' => 2]);
            $writer->table('order_lines')->insert(['id' => 2, 'order_id' => 2]);
        }
    });

    restoreScratch($sql);

    // The second order was written after the dump began, so a consistent
    // backup contains neither it nor its line.
    expect(restoredConnection()->table('orders')->count())->toBe(1)
        ->and(restoredConnection()->table('order_lines')->count())->toBe(1);
});

it('refuses a connection it cannot dump', function (): void {
    config(['database.connections.dumper_sqlite' => ['driver' => 'sqlite', 'database' => ':memory:']]);
    $sqlite = DB::connection('dumper_sqlite');

    expect((new MysqlDumper($sqlite))->supportsCurrentConnection())->toBeFalse();

    (new MysqlDumper($sqlite))->dump(fn (string $chunk) => null);
})->throws(DatabaseBackupException::class);
