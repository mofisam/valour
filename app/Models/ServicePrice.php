<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/** Price of each clothing type for each service. This is the main source of job prices. */
class ServicePrice extends Model
{
    protected static string $table = 'service_prices';
    private static ?array $map = null;

    /** @return array<int,array<int,float>> [serviceId][clothingTypeId] => price */
    public static function map(): array
    {
        if (self::$map === null) {
            self::$map = [];
            foreach (self::all('SELECT service_id, clothing_type_id, price FROM service_prices') as $r) {
                self::$map[(int) $r['service_id']][(int) $r['clothing_type_id']] = (float) $r['price'];
            }
        }
        return self::$map;
    }

    /** Price for this service + item; falls back to the item's own fallback price; 0 if neither is set. */
    public static function priceFor(?int $serviceId, int $typeId): float
    {
        if ($serviceId && isset(self::map()[$serviceId][$typeId]) && self::map()[$serviceId][$typeId] > 0) {
            return self::map()[$serviceId][$typeId];
        }
        $t = ClothingType::find($typeId);
        return $t ? (float) $t['price_per_item'] : 0.0;
    }

    /** Price list as the form needs it: [serviceId][typeId] => effective price (with fallback applied). */
    public static function effectiveMap(array $serviceIds, array $types): array
    {
        $out = [];
        foreach ($serviceIds as $sid) {
            foreach ($types as $t) {
                $specific = self::map()[(int) $sid][(int) $t['id']] ?? 0.0;
                $out[(int) $sid][(int) $t['id']] = $specific > 0 ? $specific : (float) $t['price_per_item'];
            }
        }
        return $out;
    }

    public static function set(int $serviceId, int $typeId, ?float $price): void
    {
        if ($price === null) {
            self::run('DELETE FROM service_prices WHERE service_id = ? AND clothing_type_id = ?', [$serviceId, $typeId]);
        } else {
            self::run(
                'INSERT INTO service_prices (service_id, clothing_type_id, price) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE price = VALUES(price)',
                [$serviceId, $typeId, $price]
            );
        }
        self::$map = null;
    }
}
