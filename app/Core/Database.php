<?php
declare(strict_types=1);

namespace App\Core;

use mysqli;

/** Singleton mysqli connection. */
class Database
{
    private static ?mysqli $conn = null;

    public static function connection(): mysqli
    {
        if (self::$conn === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $c = config('database');
            self::$conn = new mysqli($c['host'], $c['user'], $c['pass'], $c['name'], $c['port']);
            self::$conn->set_charset($c['charset']);
            self::$conn->query("SET time_zone = '+01:00'"); // Africa/Lagos (WAT, no DST)
        }
        return self::$conn;
    }
}
