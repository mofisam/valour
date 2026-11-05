<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Service extends Model
{
    protected static string $table = 'services';

    public static function active(): array
    {
        return self::all('SELECT * FROM services WHERE is_active = 1 ORDER BY name');
    }

    public static function everything(): array
    {
        return self::all('SELECT * FROM services ORDER BY is_active DESC, name');
    }

    /**
     * Ordered list of statuses a job of this service moves through (the admin's process steps that apply).
     * Locked steps (Pending, Delivered) are always included.
     */
    public static function workflowOf(?array $service): array
    {
        $raw = $service['workflow'] ?? 'pending,washing,ready,delivered';
        $chosen = array_filter(array_map('trim', explode(',', (string) $raw)));
        $out = [];
        foreach (job_steps() as $key => $step) {
            if ($step['locked'] || in_array($key, $chosen, true)) {
                $out[] = $key;
            }
        }
        return $out;
    }

    /** Turn posted checkbox values into the stored comma list, in process order. */
    public static function workflowFromSteps(array $steps): string
    {
        $out = [];
        foreach (job_steps() as $key => $step) {
            if ($step['locked'] || in_array($key, $steps, true)) {
                $out[] = $key;
            }
        }
        return implode(',', $out);
    }
}
