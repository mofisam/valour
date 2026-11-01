<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ClothingType extends Model
{
    protected static string $table = 'clothing_types';

    public static function active(): array
    {
        return self::all('SELECT id, name, COALESCE(display_name, name) AS label, price_per_item FROM clothing_types WHERE is_active = 1 ORDER BY label');
    }

    public static function everything(): array
    {
        return self::all('SELECT * FROM clothing_types ORDER BY is_active DESC, name');
    }
}
