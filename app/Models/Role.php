<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Role extends Model
{
    protected static string $table = 'roles';

    public static function options(): array
    {
        return self::all('SELECT id, name, label FROM roles ORDER BY id');
    }
}
