<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

class Auth
{
    private static ?array $user = null;
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 300;

    public static function user(): ?array
    {
        if (self::$user === null && ($id = Session::get('user_id'))) {
            $u = User::findWithRole((int) $id);
            if ($u && (int) $u['is_active'] === 1) {
                self::$user = $u;
            } else {
                Session::forget('user_id');
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return self::user() ? (int) self::user()['id'] : null;
    }

    public static function is(string ...$roles): bool
    {
        $u = self::user();
        return $u !== null && in_array($u['role'], $roles, true);
    }

    /** Returns null on success, or an error message. */
    public static function attempt(string $email, string $password): ?string
    {
        $lockedUntil = (int) Session::get('login_locked_until', 0);
        if ($lockedUntil > time()) {
            return 'Too many failed attempts. Try again in ' . ceil(($lockedUntil - time()) / 60) . ' minute(s).';
        }

        $user = User::findByEmail($email);
        if (!$user || !(int) $user['is_active'] || !password_verify($password, $user['password_hash'])) {
            $fails = (int) Session::get('login_fails', 0) + 1;
            Session::put('login_fails', $fails);
            if ($fails >= self::MAX_ATTEMPTS) {
                Session::put('login_locked_until', time() + self::LOCK_SECONDS);
                Session::put('login_fails', 0);
            }
            return 'Email or password is incorrect.';
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            User::update((int) $user['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        Session::forget('login_fails');
        Session::touch();
        User::update((int) $user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
        self::$user = null;
        return null;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = null;
    }
}
