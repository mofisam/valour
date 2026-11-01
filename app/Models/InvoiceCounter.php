<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class InvoiceCounter extends Model
{
    protected static string $table = 'invoice_sequences';

    /** Atomically reserve the next number for a period like "26-10" (safe under concurrent requests). */
    public static function next(string $period): int
    {
        self::run(
            'INSERT INTO invoice_sequences (period, last_number) VALUES (?, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE last_number = LAST_INSERT_ID(last_number + 1)',
            [$period]
        );
        return self::lastId();
    }
}
