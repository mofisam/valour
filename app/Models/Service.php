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
     * Ordered list of statuses a job of this service moves through.
     * Always starts with 'pending' and ends with 'delivered'; the steps in between are what the admin switched on.
     */
    public static function workflowOf(?array $service): array
    {
        $raw = $service['workflow'] ?? 'pending,washing,ready,delivered';
        $chosen = array_filter(array_map('trim', explode(',', (string) $raw)));
        return array_values(array_filter(JOB_FLOW, fn($s) => $s === 'pending' || $s === 'delivered' || in_array($s, $chosen, true)));
    }

    /** Turn posted checkbox values into the stored comma list. */
    public static function workflowFromSteps(array $steps): string
    {
        $steps = array_values(array_intersect(OPTIONAL_STEPS, $steps));
        return implode(',', array_values(array_filter(JOB_FLOW, fn($s) => $s === 'pending' || $s === 'delivered' || in_array($s, $steps, true))));
    }
}
