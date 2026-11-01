<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Expense extends Model
{
    protected static string $table = 'expenses';

    public static function categories(): array
    {
        return self::all('SELECT id, name, is_direct_cost FROM expense_categories ORDER BY name');
    }

    private static function where(array $f, array &$p): string
    {
        $sql = ' WHERE 1=1';
        if (!empty($f['from'])) { $sql .= ' AND e.expense_date >= ?'; $p[] = $f['from']; }
        if (!empty($f['to']))   { $sql .= ' AND e.expense_date <= ?'; $p[] = $f['to']; }
        if (!empty($f['category_id'])) { $sql .= ' AND e.category_id = ?'; $p[] = (int) $f['category_id']; }
        return $sql;
    }

    public static function count(array $f): int
    {
        $p = [];
        $w = self::where($f, $p);
        return (int) self::scalar('SELECT COUNT(*) FROM expenses e' . $w, $p);
    }

    public static function totalFor(array $f): float
    {
        $p = [];
        $w = self::where($f, $p);
        return (float) self::scalar('SELECT COALESCE(SUM(e.amount),0) FROM expenses e' . $w, $p);
    }

    public static function paginate(array $f, int $limit, int $offset): array
    {
        $p = [];
        $w = self::where($f, $p);
        return self::all(
            'SELECT e.*, c.name AS category_name, u.name AS created_by_name
             FROM expenses e LEFT JOIN expense_categories c ON c.id = e.category_id LEFT JOIN users u ON u.id = e.created_by' . $w .
            ' ORDER BY e.expense_date DESC, e.id DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
            $p
        );
    }
}
