<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Payment extends Model
{
    protected static string $table = 'payments';

    public static function forJob(int $jobId): array
    {
        return self::all(
            'SELECT p.*, u.name AS received_by_name FROM payments p LEFT JOIN users u ON u.id = p.received_by
             WHERE p.job_id = ? ORDER BY p.paid_at, p.id',
            [$jobId]
        );
    }

    /** Record a payment. Throws RuntimeException if it would overpay the job. */
    public static function add(int $jobId, float $amount, string $method, ?string $reference, ?string $paidAt, ?int $userId): int
    {
        return (int) self::transaction(function () use ($jobId, $amount, $method, $reference, $paidAt, $userId) {
            $job = self::first('SELECT total, amount_paid FROM jobs WHERE id = ? AND deleted_at IS NULL FOR UPDATE', [$jobId]);
            if (!$job) {
                throw new \RuntimeException('Job not found.');
            }
            $balance = round((float) $job['total'] - (float) $job['amount_paid'], 2);
            if ($amount <= 0) {
                throw new \RuntimeException('Enter an amount greater than zero.');
            }
            if ($amount > $balance + 0.001) {
                throw new \RuntimeException('That is more than the outstanding balance of ' . money($balance) . '.');
            }
            $id = self::insert([
                'job_id'      => $jobId,
                'amount'      => $amount,
                'method'      => $method,
                'reference'   => $reference ?: null,
                'paid_at'     => $paidAt ?: date('Y-m-d H:i:s'),
                'received_by' => $userId,
            ]);
            self::recalc($jobId);
            return $id;
        });
    }

    public static function remove(int $paymentId): ?int
    {
        $p = self::find($paymentId);
        if (!$p) {
            return null;
        }
        self::transaction(function () use ($p) {
            self::run('DELETE FROM payments WHERE id = ?', [(int) $p['id']]);
            self::recalc((int) $p['job_id']);
        });
        return (int) $p['job_id'];
    }

    /** Re-derive amount_paid + payment_status from the payments table. */
    public static function recalc(int $jobId): void
    {
        $paid  = (float) self::scalar('SELECT COALESCE(SUM(amount),0) FROM payments WHERE job_id = ?', [$jobId]);
        $total = (float) self::scalar('SELECT total FROM jobs WHERE id = ?', [$jobId]);
        $status = $paid <= 0 ? 'unpaid' : ($paid + 0.001 >= $total && $total > 0 ? 'paid' : 'partial');
        self::run('UPDATE jobs SET amount_paid = ?, payment_status = ? WHERE id = ?', [$paid, $status, $jobId]);
    }
}
