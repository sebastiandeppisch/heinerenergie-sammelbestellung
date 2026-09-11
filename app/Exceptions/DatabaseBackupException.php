<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class DatabaseBackupException extends Exception
{
    public static function unsupportedDriver(string $driver): self
    {
        return new self('Für die Datenbank-Verbindung "'.$driver.'" gibt es kein Backup. Unterstützt werden MySQL und MariaDB.');
    }

    public static function couldNotWrite(string $path): self
    {
        return new self('Die Backup-Datei konnte nicht geschrieben werden: '.$path);
    }

    public static function unknownBackup(string $name): self
    {
        return new self('Das Backup "'.$name.'" gibt es nicht.');
    }
}
