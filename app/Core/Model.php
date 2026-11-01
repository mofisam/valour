<?php
declare(strict_types=1);

namespace App\Core;

use mysqli;

/**
 * Base model: a thin, static wrapper around mysqli prepared statements.
 * Every query goes through run()/all()/first() so values are always bound.
 */
abstract class Model
{
    protected static string $table = '';
    protected static bool $softDeletes = false;

    public static function db(): mysqli
    {
        return Database::connection();
    }

    /** Execute a statement and return the mysqli_stmt (caller may fetch). */
    protected static function prepareAndRun(string $sql, array $params = []): \mysqli_stmt
    {
        $stmt = self::db()->prepare($sql);
        if ($params) {
            $types = '';
            foreach ($params as $p) {
                $types .= match (true) {
                    is_int($p), is_bool($p) => 'i',
                    is_float($p)            => 'd',
                    default                 => 's',
                };
            }
            $params = array_map(fn($p) => is_bool($p) ? (int) $p : $p, $params);
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt;
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(string $sql, array $params = []): array
    {
        $stmt = self::prepareAndRun($sql, $params);
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public static function first(string $sql, array $params = []): ?array
    {
        $rows = self::all($sql, $params);
        return $rows[0] ?? null;
    }

    public static function scalar(string $sql, array $params = []): mixed
    {
        $row = self::first($sql, $params);
        return $row ? array_values($row)[0] : null;
    }

    /** Run INSERT/UPDATE/DELETE. Returns affected rows. */
    public static function run(string $sql, array $params = []): int
    {
        $stmt = self::prepareAndRun($sql, $params);
        $n = $stmt->affected_rows;
        $stmt->close();
        return $n;
    }

    public static function lastId(): int
    {
        return (int) self::db()->insert_id;
    }

    /* ----- Generic CRUD on static::$table ----- */

    public static function find(int $id): ?array
    {
        $sql = 'SELECT * FROM `' . static::$table . '` WHERE id = ?';
        if (static::$softDeletes) {
            $sql .= ' AND deleted_at IS NULL';
        }
        return self::first($sql, [$id]);
    }

    public static function insert(array $data): int
    {
        $cols = implode(',', array_map(fn($c) => "`$c`", array_keys($data)));
        $marks = implode(',', array_fill(0, count($data), '?'));
        self::run('INSERT INTO `' . static::$table . "` ($cols) VALUES ($marks)", array_values($data));
        return self::lastId();
    }

    public static function update(int $id, array $data): int
    {
        $set = implode(',', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        return self::run('UPDATE `' . static::$table . "` SET $set WHERE id = ?", [...array_values($data), $id]);
    }

    public static function delete(int $id): int
    {
        if (static::$softDeletes) {
            return self::run('UPDATE `' . static::$table . '` SET deleted_at = NOW() WHERE id = ?', [$id]);
        }
        return self::run('DELETE FROM `' . static::$table . '` WHERE id = ?', [$id]);
    }

    public static function transaction(callable $fn): mixed
    {
        $db = self::db();
        $db->begin_transaction();
        try {
            $result = $fn();
            $db->commit();
            return $result;
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }
}
