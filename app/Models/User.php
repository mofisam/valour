<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        return self::first('SELECT * FROM users WHERE email = ?', [strtolower($email)]);
    }

    public static function findWithRole(int $id): ?array
    {
        return self::first(
            'SELECT u.*, r.name AS role, r.label AS role_label FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?',
            [$id]
        );
    }

    public static function listAll(): array
    {
        return self::all('SELECT u.*, r.name AS role, r.label AS role_label FROM users u JOIN roles r ON r.id = u.role_id ORDER BY u.name');
    }

    public static function emailTaken(string $email, ?int $exceptId = null): bool
    {
        return (bool) self::scalar('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?', [strtolower($email), $exceptId ?? 0]);
    }

    public static function activeAdminCount(): int
    {
        return (int) self::scalar("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='admin' AND u.is_active=1");
    }
}
