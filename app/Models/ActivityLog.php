<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\{Auth, Model};

class ActivityLog extends Model
{
    protected static string $table = 'activity_logs';

    public static function log(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void
    {
        try {
            self::insert([
                'user_id'     => Auth::id(),
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'details'     => $details,
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (\Throwable) {
            // Logging must never break the request.
        }
    }

    public static function recent(int $limit = 50): array
    {
        return self::all(
            'SELECT l.*, u.name AS user_name FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT ' . (int) $limit
        );
    }

    private static function where(array $f, array &$p): string
    {
        $sql = ' WHERE 1=1';
        if (!empty($f['user_id'])) { $sql .= ' AND l.user_id = ?'; $p[] = (int) $f['user_id']; }
        if (!empty($f['entity']))  { $sql .= ' AND l.action LIKE ?'; $p[] = $f['entity'] . '.%'; }
        if (!empty($f['from']))    { $sql .= ' AND DATE(l.created_at) >= ?'; $p[] = $f['from']; }
        if (!empty($f['to']))      { $sql .= ' AND DATE(l.created_at) <= ?'; $p[] = $f['to']; }
        return $sql;
    }

    public static function count(array $f = []): int
    {
        $p = [];
        $w = self::where($f, $p);
        return (int) self::scalar('SELECT COUNT(*) FROM activity_logs l' . $w, $p);
    }

    public static function paginate(array $f, int $limit, int $offset): array
    {
        $p = [];
        $w = self::where($f, $p);
        return self::all(
            'SELECT l.*, u.name AS user_name FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id' . $w .
            ' ORDER BY l.id DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
            $p
        );
    }

    /** Distinct areas (job, payment, customer…) for the filter drop-down. */
    public static function entities(): array
    {
        return array_column(self::all("SELECT DISTINCT SUBSTRING_INDEX(action, '.', 1) AS e FROM activity_logs WHERE action LIKE '%.%' ORDER BY e"), 'e');
    }
}
