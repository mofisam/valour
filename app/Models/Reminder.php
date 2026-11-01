<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Reminder extends Model
{
    protected static string $table = 'reminders';

    /** Pending reminders whose time has arrived. */
    public static function due(int $userId): array
    {
        return self::all('SELECT * FROM reminders WHERE user_id = ? AND is_done = 0 AND remind_at <= NOW() ORDER BY remind_at', [$userId]);
    }

    public static function dueCount(int $userId): int
    {
        try {
            return (int) self::scalar('SELECT COUNT(*) FROM reminders WHERE user_id = ? AND is_done = 0 AND remind_at <= NOW()', [$userId]);
        } catch (\Throwable) {
            return 0; // table missing (migration 002 not run yet) must not break every page
        }
    }

    public static function upcoming(int $userId, int $limit = 50): array
    {
        return self::all('SELECT * FROM reminders WHERE user_id = ? AND is_done = 0 AND remind_at > NOW() ORDER BY remind_at LIMIT ' . (int) $limit, [$userId]);
    }

    public static function completed(int $userId, int $limit = 15): array
    {
        return self::all('SELECT * FROM reminders WHERE user_id = ? AND is_done = 1 ORDER BY completed_at DESC LIMIT ' . (int) $limit, [$userId]);
    }

    public static function owned(int $id, int $userId): ?array
    {
        return self::first('SELECT * FROM reminders WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    /** Mark done. Repeating reminders roll forward to their next future occurrence instead of closing. */
    public static function complete(array $r): void
    {
        if ($r['repeat_rule'] === 'none') {
            self::update((int) $r['id'], ['is_done' => 1, 'completed_at' => date('Y-m-d H:i:s')]);
            return;
        }
        $step = ['daily' => '+1 day', 'weekly' => '+1 week', 'monthly' => '+1 month'][$r['repeat_rule']];
        $next = new \DateTime($r['remind_at']);
        $now = new \DateTime();
        do {
            $next->modify($step);
        } while ($next <= $now);
        self::update((int) $r['id'], ['remind_at' => $next->format('Y-m-d H:i:s')]);
    }

    public static function snooze(int $id, string $until): void
    {
        self::update($id, ['remind_at' => $until, 'is_done' => 0]);
    }
}
