<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    protected static string $table = 'settings';
    private static ?array $cache = null;

    public static function all_(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                foreach (self::all('SELECT `key`, `value` FROM settings') as $r) {
                    self::$cache[$r['key']] = $r['value'];
                }
            } catch (\Throwable) {
                // DB not ready yet (e.g. during install)
            }
        }
        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all_();
        return (isset($all[$key]) && $all[$key] !== '') ? $all[$key] : $default;
    }

    public static function set(string $key, ?string $value): void
    {
        self::run('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', [$key, $value]);
        self::$cache = null;
    }
}
