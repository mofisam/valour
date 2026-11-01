<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Customer extends Model
{
    protected static string $table = 'customers';
    protected static bool $softDeletes = true;

    private const OWED = 'GREATEST(COALESCE(s.total_billed,0) - COALESCE(s.total_paid,0), 0)';
    private const FROM = ' FROM customers c LEFT JOIN customer_spending s ON s.customer_id = c.id ';

    private static function where(string $q, bool $owing, array &$params): string
    {
        $sql = ' WHERE c.deleted_at IS NULL';
        if ($q !== '') {
            $sql .= ' AND (c.full_name LIKE ? OR c.phone_number LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like);
        }
        if ($owing) {
            $sql .= ' AND ' . self::OWED . ' > 0.005';
        }
        return $sql;
    }

    public static function count(string $q = '', bool $owing = false): int
    {
        $p = [];
        $where = self::where($q, $owing, $p);
        return (int) self::scalar('SELECT COUNT(*)' . self::FROM . $where, $p);
    }

    public static function paginate(string $q, int $limit, int $offset, bool $owing = false): array
    {
        $p = [];
        $where = self::where($q, $owing, $p);
        return self::all(
            'SELECT c.*, COALESCE(s.job_count,0) AS job_count, COALESCE(s.total_paid,0) AS total_paid, ' . self::OWED . ' AS outstanding' .
            self::FROM . $where . ' ORDER BY ' . ($owing ? 'outstanding DESC, ' : '') . 'c.full_name LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
            $p
        );
    }

    /** How many customers owe money and how much in total. */
    public static function owingSummary(): array
    {
        $row = self::first(
            'SELECT COUNT(*) AS customers, COALESCE(SUM(' . self::OWED . '),0) AS total' . self::FROM .
            ' WHERE c.deleted_at IS NULL AND ' . self::OWED . ' > 0.005'
        );
        return ['customers' => (int) ($row['customers'] ?? 0), 'total' => (float) ($row['total'] ?? 0)];
    }

    public static function topDebtors(int $limit = 5): array
    {
        return self::all(
            'SELECT c.id, c.full_name, c.phone_number, ' . self::OWED . ' AS outstanding' . self::FROM .
            ' WHERE c.deleted_at IS NULL AND ' . self::OWED . ' > 0.005 ORDER BY outstanding DESC LIMIT ' . (int) $limit
        );
    }

    /** Small result set for the job-form autocomplete (includes what they already owe). */
    public static function search(string $q, int $limit = 8): array
    {
        $like = '%' . $q . '%';
        return self::all(
            'SELECT c.id, c.full_name, c.phone_number, ' . self::OWED . ' AS outstanding' . self::FROM .
            ' WHERE c.deleted_at IS NULL AND (c.full_name LIKE ? OR c.phone_number LIKE ?) ORDER BY c.full_name LIMIT ' . (int) $limit,
            [$like, $like]
        );
    }

    public static function outstandingFor(int $id): float
    {
        return (float) self::scalar('SELECT ' . self::OWED . self::FROM . ' WHERE c.id = ?', [$id]);
    }

    public static function withStats(int $id): ?array
    {
        return self::first(
            'SELECT c.*, COALESCE(s.job_count,0) AS job_count, COALESCE(s.total_paid,0) AS total_paid, COALESCE(s.total_billed,0) AS total_billed, ' .
            self::OWED . ' AS outstanding' . self::FROM . ' WHERE c.id = ? AND c.deleted_at IS NULL',
            [$id]
        );
    }

    public static function birthdaysThisMonth(): array
    {
        return self::all(
            'SELECT id, full_name, phone_number, birthday FROM customers
             WHERE deleted_at IS NULL AND birthday IS NOT NULL AND MONTH(birthday) = MONTH(CURDATE())
             ORDER BY DAY(birthday)'
        );
    }
}
