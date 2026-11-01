<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class JobItem extends Model
{
    protected static string $table = 'job_items';

    public static function forJob(int $jobId): array
    {
        return self::all(
            'SELECT ji.*, COALESCE(ct.display_name, ct.name, "Item") AS type_label
             FROM job_items ji LEFT JOIN clothing_types ct ON ct.id = ji.clothing_type_id
             WHERE ji.job_id = ? ORDER BY ji.id',
            [$jobId]
        );
    }
}
