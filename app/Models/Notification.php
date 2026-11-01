<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/** In-app notifications for admins (e.g. "a job is waiting for a price"). */
class Notification extends Model
{
    protected static string $table = 'notifications';

    public static function notifyAdmins(string $type, string $title, ?string $body, ?string $link): void
    {
        self::run(
            "INSERT INTO notifications (user_id, type, title, body, link)
             SELECT u.id, ?, ?, ?, ? FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'admin' AND u.is_active = 1",
            [$type, $title, $body, $link]
        );
    }

    public static function unreadCount(int $userId): int
    {
        try {
            return (int) self::scalar('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$userId]);
        } catch (\Throwable) {
            return 0; // table missing (migration 004 not run yet) must not break every page
        }
    }

    public static function unread(int $userId, int $limit = 20): array
    {
        return self::all('SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY id DESC LIMIT ' . (int) $limit, [$userId]);
    }

    public static function recent(int $userId, int $limit = 50): array
    {
        return self::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT ' . (int) $limit, [$userId]);
    }

    public static function markRead(int $id, int $userId): void
    {
        self::run('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public static function markAllRead(int $userId): void
    {
        self::run('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0', [$userId]);
    }

    /** Clear everyone's notification about a given link once it is dealt with (e.g. the job got priced). */
    public static function resolveLink(string $link): void
    {
        self::run('UPDATE notifications SET is_read = 1 WHERE link = ? AND is_read = 0', [$link]);
    }
}
