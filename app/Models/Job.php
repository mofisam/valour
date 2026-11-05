<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Job extends Model
{
    protected static string $table = 'jobs';
    protected static bool $softDeletes = true;

    private const FROM = ' FROM jobs j
        LEFT JOIN customers c ON c.id = j.customer_id
        LEFT JOIN services s ON s.id = j.service_id ';

    private static function where(array $f, array &$p): string
    {
        $sql = ' WHERE j.deleted_at IS NULL';
        if (!empty($f['q'])) {
            $sql .= ' AND (j.invoice_number LIKE ? OR c.full_name LIKE ? OR c.phone_number LIKE ?)';
            $like = '%' . $f['q'] . '%';
            array_push($p, $like, $like, $like);
        }
        if (!empty($f['status']) && in_array($f['status'], job_statuses(), true)) {
            $sql .= ' AND j.status = ?';
            $p[] = $f['status'];
        }
        if (!empty($f['payment_status']) && in_array($f['payment_status'], PAYMENT_STATUSES, true)) {
            $sql .= ' AND j.payment_status = ?';
            $p[] = $f['payment_status'];
        }
        if (!empty($f['customer_id'])) {
            $sql .= ' AND j.customer_id = ?';
            $p[] = (int) $f['customer_id'];
        }
        if (!empty($f['from'])) {
            $sql .= ' AND j.job_date >= ?';
            $p[] = $f['from'];
        }
        if (!empty($f['to'])) {
            $sql .= ' AND j.job_date <= ?';
            $p[] = $f['to'];
        }
        if (!empty($f['draft'])) {
            $sql .= ' AND j.is_draft = 1';
        }
        if (!empty($f['pricing'])) {
            $sql .= ' AND j.is_draft = 0 AND j.needs_pricing = 1';
        }
        if (!empty($f['owing'])) {
            $sql .= " AND j.is_draft = 0 AND j.status <> 'cancelled' AND j.total - j.amount_paid > 0.005";
        }
        return $sql;
    }

    /** Counts for the tab bar on the jobs list. */
    public static function tabCounts(): array
    {
        $rows = self::all('SELECT status, COUNT(*) c FROM jobs WHERE deleted_at IS NULL AND is_draft = 0 GROUP BY status');
        $out = array_fill_keys(job_statuses(), 0) + ['all' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['c'];
            $out['all'] += (int) $r['c'];
        }
        $out['owing'] = (int) self::scalar("SELECT COUNT(*) FROM jobs WHERE deleted_at IS NULL AND is_draft = 0 AND status <> 'cancelled' AND total - amount_paid > 0.005");
        $out['pricing'] = (int) self::scalar('SELECT COUNT(*) FROM jobs WHERE deleted_at IS NULL AND is_draft = 0 AND needs_pricing = 1');
        $out['drafts'] = (int) self::scalar('SELECT COUNT(*) FROM jobs WHERE deleted_at IS NULL AND is_draft = 1');
        return $out;
    }

    public static function count(array $filters = []): int
    {
        $p = [];
        $where = self::where($filters, $p);
        return (int) self::scalar('SELECT COUNT(*)' . self::FROM . $where, $p);
    }

    public static function paginate(array $filters, int $limit, int $offset): array
    {
        $p = [];
        $where = self::where($filters, $p);
        return self::all(
            'SELECT j.*, c.full_name AS customer_name, c.phone_number, s.name AS service_name, s.workflow AS service_workflow' . self::FROM . $where .
            ' ORDER BY j.job_date DESC, j.id DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
            $p
        );
    }

    public static function full(int $id): ?array
    {
        $job = self::first(
            'SELECT j.*, c.full_name AS customer_name, c.phone_number, c.address AS customer_address, c.email AS customer_email,
                    s.name AS service_name, s.pricing_model, s.workflow, u.name AS created_by_name' . self::FROM . '
             LEFT JOIN users u ON u.id = j.created_by
             WHERE j.id = ? AND j.deleted_at IS NULL',
            [$id]
        );
        if ($job) {
            $job['items'] = JobItem::forJob($id);
            $job['steps'] = Service::workflowOf($job);
            $job['payments'] = Payment::forJob($id);
            $job['balance'] = max(0, (float) $job['total'] - (float) $job['amount_paid']);
        }
        return $job;
    }

    /**
     * Insert or update a job and replace its items in one transaction.
     * @return int job id
     */
    public static function saveWithItems(array $job, array $items, ?int $id = null): int
    {
        return (int) self::transaction(function () use ($job, $items, $id) {
            if ($id === null) {
                $id = self::insert($job);
            } else {
                self::update($id, $job);
                self::run('DELETE FROM job_items WHERE job_id = ?', [$id]);
            }
            foreach ($items as $it) {
                JobItem::insert(['job_id' => $id] + $it);
            }
            Payment::recalc($id); // total may have changed
            return $id;
        });
    }

    public static function setStatus(int $id, string $status): void
    {
        self::update($id, ['status' => $status]);
    }

    public static function setInvoiceNumber(int $id, string $number): void
    {
        self::update($id, ['invoice_number' => $number, 'is_draft' => 0]);
    }
}
