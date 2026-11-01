<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\ServicePrice;

/**
 * Single source of truth for job maths.
 *
 * Prices come from the service price list the admin maintains (Services & prices).
 *  - Admin:  may type a price per line; a blank price falls back to the service price list.
 *  - Staff:  cannot set prices. A job a staff member creates is priced at 0 and flagged "needs pricing";
 *            the admin is notified and prices it. (When a staff member edits a job the admin already
 *            priced, the existing prices are kept.)
 */
class PricingService
{
    public const COLOURS = ['white' => 'White', 'coloured' => 'Coloured'];
    public const LENGTHS = ['long' => 'Long', 'short' => 'Short', 'big' => 'Big', 'small' => 'Small', 'nil' => 'Nil'];

    /**
     * @param array      $input        posted form data
     * @param array|null $service      row from services
     * @param bool       $canSetPrice  true for admins
     * @param array|null $existing     Job::full() row when editing
     * @return array{errors:array, job:array, items:array}
     */
    public static function build(array $input, ?array $service, bool $canSetPrice = true, ?array $existing = null): array
    {
        $errors = [];
        $items = [];
        $perKg = $service && $service['pricing_model'] === 'per_kg';
        $perHour = $service && $service['pricing_model'] === 'per_hour';
        $byValue = $perKg || $perHour;   // priced by weight/time, not by the items listed
        $serviceId = $service ? (int) $service['id'] : null;
        $unpriced = false;

        $existingPrice = [];
        foreach (($existing['items'] ?? []) as $it) {
            if ($it['clothing_type_id']) {
                $existingPrice[(int) $it['clothing_type_id']] ??= (float) $it['price_per_unit'];
            }
        }

        $totalKg = null;
        $pricePerKg = null;
        $minutes = null;
        $pricePerHour = null;
        $subtotal = 0.0;
        $totalClothes = 0;

        foreach ((array) ($input['items'] ?? []) as $row) {
            $typeId = (int) ($row['clothing_type_id'] ?? 0);
            $qty    = (int) ($row['quantity'] ?? 0);
            if ($typeId === 0 && $qty === 0) {
                continue; // blank row
            }
            if ($typeId === 0 || $qty < 1) {
                $errors['items'] = 'Each item needs a clothing type and a quantity of at least 1.';
                continue;
            }

            if ($byValue) {
                $price = 0.0;
            } elseif ($canSetPrice) {
                $posted = (string) ($row['price_per_unit'] ?? '');
                $price = $posted !== '' ? max(0.0, (float) $posted) : ServicePrice::priceFor($serviceId, $typeId);
            } else {
                $price = $existingPrice[$typeId] ?? 0.0;
                if ($price <= 0) {
                    $unpriced = true;
                }
            }

            $amount = round($qty * $price, 2);
            $color  = (string) ($row['color'] ?? '');
            $length = (string) ($row['length'] ?? '');
            $items[] = [
                'clothing_type_id' => $typeId,
                'color'            => isset(self::COLOURS[$color]) ? $color : 'white',
                'length'           => isset(self::LENGTHS[$length]) ? $length : 'nil',
                'quantity'         => $qty,
                'price_per_unit'   => $price,
                'amount'           => $amount,
            ];
            $totalClothes += $qty;
            $subtotal += $amount;
        }

        if ($perKg) {
            $totalKg = (float) ($input['total_kg'] ?? 0);
            if ($canSetPrice) {
                $pricePerKg = ($input['price_per_kg'] ?? '') !== '' ? (float) $input['price_per_kg'] : (float) $service['price_per_kg'];
            } else {
                $pricePerKg = (float) ($existing['price_per_kg'] ?? 0);
                if ($pricePerKg <= 0) {
                    $unpriced = true;
                }
            }
            if ($totalKg <= 0) {
                $errors['total_kg'] = 'Enter the weight in kg.';
            }
            $subtotal = round($totalKg * (float) $pricePerKg, 2);
        } elseif ($perHour) {
            $minutes = max(0, (int) ($input['duration_hours'] ?? 0) * 60 + (int) ($input['duration_mins'] ?? 0));
            if ($canSetPrice) {
                $pricePerHour = ($input['price_per_hour'] ?? '') !== '' ? (float) $input['price_per_hour'] : (float) $service['price_per_hour'];
            } else {
                $pricePerHour = (float) ($existing['price_per_hour'] ?? 0);
                if ($pricePerHour <= 0) {
                    $unpriced = true;
                }
            }
            if ($minutes <= 0) {
                $errors['duration'] = 'Enter how long the machine was used.';
            }
            $subtotal = round($minutes / 60 * (float) $pricePerHour, 2);
        } elseif (!$items && !isset($errors['items'])) {
            $errors['items'] = 'Add at least one clothing item.';
        }

        $subtotal = round($subtotal, 2);

        // Discounts are a pricing decision: admin only. Staff edits keep whatever the job already had.
        if ($canSetPrice) {
            $discountType  = in_array($input['discount_type'] ?? 'none', ['none', 'percent', 'fixed'], true) ? $input['discount_type'] : 'none';
            $discountValue = max(0.0, (float) ($input['discount_value'] ?? 0));
        } else {
            $discountType  = $existing['discount_type'] ?? 'none';
            $discountValue = (float) ($existing['discount_value'] ?? 0);
        }

        $discountAmount = 0.0;
        if ($discountType === 'percent') {
            $discountValue = min(100.0, $discountValue);
            $discountAmount = round($subtotal * $discountValue / 100, 2);
        } elseif ($discountType === 'fixed') {
            $discountAmount = min($subtotal, round($discountValue, 2));
        } else {
            $discountValue = 0.0;
        }

        return [
            'errors' => $errors,
            'items'  => $items,
            'job'    => [
                'total_clothes'   => $totalClothes,
                'total_kg'        => $totalKg,
                'price_per_kg'    => $pricePerKg,
                'duration_minutes'=> $minutes,
                'price_per_hour'  => $pricePerHour,
                'subtotal'        => $subtotal,
                'discount_type'   => $discountType,
                'discount_value'  => $discountValue,
                'discount_amount' => $discountAmount,
                'total'           => round($subtotal - $discountAmount, 2),
                'needs_pricing'   => $canSetPrice ? 0 : ($unpriced ? 1 : 0),
            ],
        ];
    }
}
