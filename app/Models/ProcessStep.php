<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/** The statuses a job can move through. Edited by the admin; services pick which ones apply to them. */
class ProcessStep extends Model
{
    protected static string $table = 'process_steps';

    public static function ordered(): array
    {
        return self::all('SELECT * FROM process_steps ORDER BY sort_order, id');
    }

    public static function findByKey(string $key): ?array
    {
        return self::first('SELECT * FROM process_steps WHERE step_key = ?', [$key]);
    }

    public static function jobsUsing(string $key): int
    {
        return (int) self::scalar('SELECT COUNT(*) FROM jobs WHERE status = ? AND deleted_at IS NULL', [$key]);
    }

    /** Take a removed step out of every service's process. */
    public static function removeFromServices(string $key): void
    {
        foreach (self::all('SELECT id, workflow FROM services') as $s) {
            $steps = array_filter(array_map('trim', explode(',', (string) $s['workflow'])), fn($x) => $x !== '' && $x !== $key);
            self::run('UPDATE services SET workflow = ? WHERE id = ?', [implode(',', $steps), (int) $s['id']]);
        }
    }

    /** Make a unique, safe key from a label: "Folding & packing" -> folding_packing */
    public static function makeKey(string $label): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower($label)), '_');
        $base = substr($base !== '' ? $base : 'step', 0, 32);
        if (in_array($base, ['cancelled', 'draft', 'all', 'owing', 'pricing', 'drafts'], true)) {
            $base .= '_step';
        }
        $key = $base;
        for ($n = 2; self::findByKey($key); $n++) {
            $key = $base . '_' . $n;
        }
        return $key;
    }
}
