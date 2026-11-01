<?php
declare(strict_types=1);

namespace App\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => base_path() ?: '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('ve_session');
        session_start();

        // Rotate flash data: what was "next" becomes "current" for this request.
        $_SESSION['_flash_now'] = $_SESSION['_flash_next'] ?? [];
        $_SESSION['_flash_next'] = [];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Record that the person is using the app right now. */
    public static function touch(): void
    {
        $_SESSION['last_activity'] = time();
    }

    /** Seconds since the person last used the app (0 if unknown). */
    public static function idleSeconds(): int
    {
        return isset($_SESSION['last_activity']) ? max(0, time() - (int) $_SESSION['last_activity']) : 0;
    }

    public static function idleLimit(): int
    {
        return max(1, (int) config('app.idle_minutes', 10)) * 60;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /* ----- Flash messages ----- */

    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash_next']['messages'][] = ['type' => $type, 'message' => $message];
    }

    public static function messages(): array
    {
        return $_SESSION['_flash_now']['messages'] ?? [];
    }

    public static function flashErrors(array $errors, array $old = []): void
    {
        $_SESSION['_flash_next']['errors'] = $errors;
        unset($old['password'], $old['password_confirmation'], $old['_token']);
        $_SESSION['_flash_next']['old'] = $old;
    }

    public static function error(string $key): ?string
    {
        return $_SESSION['_flash_now']['errors'][$key] ?? null;
    }

    public static function hasErrors(): bool
    {
        return !empty($_SESSION['_flash_now']['errors']);
    }

    public static function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_flash_now']['old'][$key] ?? $default;
    }
}
