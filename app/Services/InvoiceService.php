<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\{InvoiceCounter, Job};

class InvoiceService
{
    /** e.g. VE-26-10-001 = prefix, year, month, running number for that month. */
    public static function generateNumber(?string $period = null): string
    {
        $period ??= date('y-m');
        $n = InvoiceCounter::next($period);
        $prefix = preg_replace('/[^A-Za-z0-9]/', '', (string) setting('invoice_prefix', 'VE')) ?: 'VE';
        return sprintf('%s-%s-%03d', strtoupper($prefix), $period, $n);
    }

    /** Give a draft job its permanent invoice number (no-op if it already has one). */
    public static function finalize(int $jobId): string
    {
        $job = Job::find($jobId);
        if ($job && $job['invoice_number']) {
            return $job['invoice_number'];
        }
        $number = self::generateNumber();
        Job::setInvoiceNumber($jobId, $number);
        return $number;
    }
}
