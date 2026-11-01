<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Model;

/**
 * Accounting definitions used everywhere (dashboard + reports):
 *  - Revenue      = total billed on jobs (not draft, not cancelled), by job date.
 *  - Cash / Bank  = payments actually received, by payment date. "Cash" = cash payments, "Bank" = transfer/POS/other.
 *  - Receivables  = billed but not yet paid (revenue minus payments received on those jobs).
 *  - Direct costs = expenses in categories flagged "direct cost" (detergent, power, water…).
 *  - Gross profit = revenue - direct costs.     Net profit = revenue - all expenses.
 */
class ReportService extends Model
{
    protected static string $table = 'jobs';

    private const BILLABLE = "j.deleted_at IS NULL AND j.is_draft = 0 AND j.status <> 'cancelled'";

    /** Counts and money for the dashboard cards. */
    public static function dashboard(): array
    {
        $today = date('Y-m-d');
        return [
            'jobs_today'     => (int) self::scalar('SELECT COUNT(*) FROM jobs j WHERE ' . self::BILLABLE . ' AND j.job_date = ?', [$today]),
            'open_jobs'      => (int) self::scalar("SELECT COUNT(*) FROM jobs j WHERE j.deleted_at IS NULL AND j.is_draft = 0 AND j.status NOT IN ('ready','delivered','cancelled')"),
            'ready_jobs'     => (int) self::scalar("SELECT COUNT(*) FROM jobs j WHERE j.deleted_at IS NULL AND j.is_draft = 0 AND j.status = 'ready'"),
            'expenses_month' => (float) self::scalar('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date >= ?', [date('Y-m-01')]),
        ];
    }

    /** Daily revenue (billed) for the last N days, zero-filled. */
    public static function revenueSeries(int $days = 14): array
    {
        $from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $rows = self::all('SELECT j.job_date d, SUM(j.total) t FROM jobs j WHERE ' . self::BILLABLE . ' AND j.job_date >= ? GROUP BY j.job_date', [$from]);
        $map = array_column($rows, 't', 'd');
        $out = ['labels' => [], 'values' => []];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $out['labels'][] = date('d M', strtotime($d));
            $out['values'][] = (float) ($map[$d] ?? 0);
        }
        return $out;
    }

    /** Numbers for the admin dashboard: month-to-date vs the same days last month, trend, status mix. */
    public static function adminInsights(): array
    {
        $monthStart = date('Y-m-01');
        $dayN = (int) date('j');
        $lastStart = date('Y-m-01', strtotime('first day of last month'));
        $lastEndFull = date('Y-m-t', strtotime($lastStart));
        $lastSameDay = min($lastEndFull, date('Y-m-d', strtotime($lastStart . ' +' . ($dayN - 1) . ' days')));

        $rev = fn(string $a, string $b) => (float) self::scalar('SELECT COALESCE(SUM(j.total),0) FROM jobs j WHERE ' . self::BILLABLE . ' AND j.job_date BETWEEN ? AND ?', [$a, $b]);
        $exp = fn(string $a, string $b) => (float) self::scalar('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date BETWEEN ? AND ?', [$a, $b]);
        $cash = fn(string $a, string $b) => (float) self::scalar('SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE(paid_at) BETWEEN ? AND ?', [$a, $b]);
        $today = date('Y-m-d');

        $revMtd = $rev($monthStart, $today);
        $revPrev = $rev($lastStart, $lastSameDay);
        $expMtd = $exp($monthStart, $today);
        $expPrev = $exp($lastStart, $lastSameDay);

        $m = self::first('SELECT COALESCE(SUM(j.total),0) AS billed, COALESCE(SUM(j.amount_paid),0) AS paid, COALESCE(AVG(j.total),0) AS avg_job, COUNT(*) AS jobs
                          FROM jobs j WHERE ' . self::BILLABLE . ' AND j.job_date >= ?', [$monthStart]);

        // 6-month revenue vs expenses
        $months = [];
        for ($k = 5; $k >= 0; $k--) {
            $months[date('Y-m', strtotime("first day of -$k month"))] = ['label' => date('M', strtotime("first day of -$k month")), 'revenue' => 0.0, 'expenses' => 0.0];
        }
        $from = array_key_first($months) . '-01';
        foreach (self::all("SELECT DATE_FORMAT(j.job_date,'%Y-%m') ym, SUM(j.total) t FROM jobs j WHERE " . self::BILLABLE . ' AND j.job_date >= ? GROUP BY ym', [$from]) as $r) {
            if (isset($months[$r['ym']])) $months[$r['ym']]['revenue'] = (float) $r['t'];
        }
        foreach (self::all("SELECT DATE_FORMAT(expense_date,'%Y-%m') ym, SUM(amount) t FROM expenses WHERE expense_date >= ? GROUP BY ym", [$from]) as $r) {
            if (isset($months[$r['ym']])) $months[$r['ym']]['expenses'] = (float) $r['t'];
        }

        return [
            'rev_mtd' => $revMtd, 'rev_prev' => $revPrev,
            'exp_mtd' => $expMtd, 'exp_prev' => $expPrev,
            'net_mtd' => $revMtd - $expMtd, 'net_prev' => $revPrev - $expPrev,
            'cash_mtd' => $cash($monthStart, $today), 'cash_prev' => $cash($lastStart, $lastSameDay),
            'billed_month' => (float) $m['billed'], 'collected_month' => (float) $m['paid'],
            'avg_job' => (float) $m['avg_job'], 'jobs_month' => (int) $m['jobs'],
            'new_customers' => (int) self::scalar('SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL AND created_at >= ?', [$monthStart]),
            'months' => array_values($months),
            'status_counts' => array_column(self::all(
                'SELECT status, COUNT(*) c FROM jobs WHERE deleted_at IS NULL AND is_draft = 0 AND job_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY status'
            ), 'c', 'status'),
            'top_services' => self::all(
                "SELECT COALESCE(s.name,'Unspecified') AS name, COUNT(*) AS jobs, SUM(j.total) AS billed
                 FROM jobs j LEFT JOIN services s ON s.id = j.service_id
                 WHERE " . self::BILLABLE . ' AND j.job_date >= ? GROUP BY s.name ORDER BY billed DESC LIMIT 4',
                [$monthStart]
            ),
            'activity' => self::all('SELECT l.*, u.name AS user_name FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 6'),
        ];
    }

    /** Jobs that need someone's attention today. */
    public static function attention(): array
    {
        $base = 'SELECT j.id, j.invoice_number, j.due_date, j.status, j.updated_at, j.total, j.amount_paid, c.full_name AS customer_name
                 FROM jobs j LEFT JOIN customers c ON c.id = j.customer_id
                 WHERE j.deleted_at IS NULL AND j.is_draft = 0 AND ';
        return [
            'pricing' => self::all($base . "j.needs_pricing = 1 ORDER BY j.id LIMIT 6"),
            'overdue' => self::all($base . "j.status NOT IN ('ready','delivered','cancelled') AND j.due_date IS NOT NULL AND j.due_date < CURDATE() ORDER BY j.due_date LIMIT 6"),
            'waiting' => self::all($base . "j.status = 'ready' AND j.updated_at < DATE_SUB(NOW(), INTERVAL 3 DAY) ORDER BY j.updated_at LIMIT 6"),
            'drafts'  => (int) self::scalar('SELECT COUNT(*) FROM jobs WHERE deleted_at IS NULL AND is_draft = 1'),
        ];
    }

    public static function recentJobs(int $limit = 8): array
    {
        return self::all(
            'SELECT j.*, c.full_name AS customer_name FROM jobs j LEFT JOIN customers c ON c.id = j.customer_id
             WHERE j.deleted_at IS NULL ORDER BY j.id DESC LIMIT ' . (int) $limit
        );
    }

    /** Full report for a date range (inclusive). */
    public static function summary(string $from, string $to): array
    {
        $jobs = self::first(
            "SELECT COUNT(*) AS jobs,
                    COALESCE(SUM(j.status = 'delivered'),0) AS delivered,
                    COALESCE(SUM(j.total),0) AS revenue,
                    COALESCE(SUM(j.total - j.amount_paid),0) AS receivables
             FROM jobs j WHERE " . self::BILLABLE . ' AND j.job_date BETWEEN ? AND ?',
            [$from, $to]
        );
        $exp = self::first(
            'SELECT COALESCE(SUM(e.amount),0) AS total, COALESCE(SUM(CASE WHEN c.is_direct_cost = 1 THEN e.amount ELSE 0 END),0) AS direct
             FROM expenses e LEFT JOIN expense_categories c ON c.id = e.category_id WHERE e.expense_date BETWEEN ? AND ?',
            [$from, $to]
        );
        $cash = self::first(
            "SELECT COALESCE(SUM(amount),0) AS total, COALESCE(SUM(CASE WHEN method = 'cash' THEN amount ELSE 0 END),0) AS cash
             FROM payments WHERE DATE(paid_at) BETWEEN ? AND ?",
            [$from, $to]
        );

        $revenue = (float) $jobs['revenue'];
        $count = (int) $jobs['jobs'];
        $expenses = (float) $exp['total'];
        $direct = (float) $exp['direct'];

        return [
            'jobs'          => $count,
            'delivered'     => (int) $jobs['delivered'],
            'revenue'       => $revenue,
            'avg_revenue'   => $count ? $revenue / $count : 0.0,
            'receivables'   => max(0.0, (float) $jobs['receivables']),
            'expenses'      => $expenses,
            'direct_costs'  => $direct,
            'operating'     => $expenses - $direct,
            'cash_total'    => (float) $cash['total'],
            'cash_cash'     => (float) $cash['cash'],
            'cash_bank'     => (float) $cash['total'] - (float) $cash['cash'],
            'gross_profit'  => $revenue - $direct,
            'net_profit'    => $revenue - $expenses,
            'trend'         => self::trend($from, $to),
            'collection_rate' => $revenue > 0 ? max(0.0, min(100.0, round(($revenue - max(0.0, (float) $jobs['receivables'])) / $revenue * 100, 1))) : 0.0,
            'by_service'    => self::all(
                "SELECT COALESCE(s.name,'Unspecified') AS name, COUNT(*) AS jobs, SUM(j.total) AS billed
                 FROM jobs j LEFT JOIN services s ON s.id = j.service_id
                 WHERE " . self::BILLABLE . ' AND j.job_date BETWEEN ? AND ? GROUP BY s.name ORDER BY billed DESC',
                [$from, $to]
            ),
            'by_method'     => self::all('SELECT method, COUNT(*) AS payments, SUM(amount) AS total FROM payments WHERE DATE(paid_at) BETWEEN ? AND ? GROUP BY method ORDER BY total DESC', [$from, $to]),
            'by_category'   => self::all(
                "SELECT COALESCE(c.name,'Uncategorised') AS name, COALESCE(c.is_direct_cost,0) AS is_direct, SUM(e.amount) AS total
                 FROM expenses e LEFT JOIN expense_categories c ON c.id = e.category_id
                 WHERE e.expense_date BETWEEN ? AND ? GROUP BY c.name, c.is_direct_cost ORDER BY total DESC",
                [$from, $to]
            ),
            'top_customers' => self::all(
                "SELECT c.id, c.full_name, COUNT(j.id) AS jobs, SUM(j.total) AS billed, SUM(j.amount_paid) AS paid
                 FROM jobs j JOIN customers c ON c.id = j.customer_id
                 WHERE " . self::BILLABLE . ' AND j.job_date BETWEEN ? AND ? GROUP BY c.id, c.full_name ORDER BY billed DESC LIMIT 10',
                [$from, $to]
            ),
        ];
    }

    /** Customers with unpaid balances, split into age buckets by job date (for the Receivables page). */
    public static function receivables(): array
    {
        $rows = self::all(
            "SELECT c.id, c.full_name, c.phone_number, COUNT(*) AS jobs, MIN(j.job_date) AS oldest,
                    SUM(j.total - j.amount_paid) AS total,
                    SUM(CASE WHEN DATEDIFF(CURDATE(), j.job_date) <= 30 THEN j.total - j.amount_paid ELSE 0 END) AS b30,
                    SUM(CASE WHEN DATEDIFF(CURDATE(), j.job_date) BETWEEN 31 AND 60 THEN j.total - j.amount_paid ELSE 0 END) AS b60,
                    SUM(CASE WHEN DATEDIFF(CURDATE(), j.job_date) BETWEEN 61 AND 90 THEN j.total - j.amount_paid ELSE 0 END) AS b90,
                    SUM(CASE WHEN DATEDIFF(CURDATE(), j.job_date) > 90 THEN j.total - j.amount_paid ELSE 0 END) AS b90plus
             FROM jobs j JOIN customers c ON c.id = j.customer_id AND c.deleted_at IS NULL
             WHERE " . self::BILLABLE . ' AND j.total - j.amount_paid > 0.005
             GROUP BY c.id, c.full_name, c.phone_number ORDER BY total DESC'
        );
        $totals = ['total' => 0.0, 'b30' => 0.0, 'b60' => 0.0, 'b90' => 0.0, 'b90plus' => 0.0];
        foreach ($rows as $r) {
            foreach ($totals as $k => $_) {
                $totals[$k] += (float) $r[$k];
            }
        }
        return ['rows' => $rows, 'totals' => $totals];
    }

    /** SQL fragment + params for a "Bank / Cash" payment filter. $method: '' | cash | bank | transfer | pos | other */
    private static function methodWhere(string $method, array &$p): string
    {
        if ($method === 'cash') {
            return " AND p.method = 'cash'";
        }
        if ($method === 'bank') {
            return " AND p.method <> 'cash'";
        }
        if (in_array($method, ['transfer', 'pos', 'other'], true)) {
            $p[] = $method;
            return ' AND p.method = ?';
        }
        return '';
    }

    /** Totals + daily split for the Bank & Cash page. */
    public static function cashSummary(string $from, string $to, string $method = ''): array
    {
        $p = [$from, $to];
        $where = ' WHERE DATE(p.paid_at) BETWEEN ? AND ?' . self::methodWhere($method, $p);
        $t = self::first("SELECT COUNT(*) AS n, COALESCE(SUM(p.amount),0) AS total,
                          COALESCE(SUM(CASE WHEN p.method = 'cash' THEN p.amount ELSE 0 END),0) AS cash,
                          COALESCE(SUM(CASE WHEN p.method = 'transfer' THEN p.amount ELSE 0 END),0) AS transfer,
                          COALESCE(SUM(CASE WHEN p.method = 'pos' THEN p.amount ELSE 0 END),0) AS pos,
                          COALESCE(SUM(CASE WHEN p.method = 'other' THEN p.amount ELSE 0 END),0) AS other
                          FROM payments p" . $where, $p);

        $p2 = [$from, $to];
        $w2 = ' WHERE DATE(p.paid_at) BETWEEN ? AND ?' . self::methodWhere($method, $p2);
        $rows = self::all("SELECT DATE(p.paid_at) d, SUM(CASE WHEN p.method = 'cash' THEN p.amount ELSE 0 END) c, SUM(CASE WHEN p.method <> 'cash' THEN p.amount ELSE 0 END) b FROM payments p" . $w2 . ' GROUP BY DATE(p.paid_at)', $p2);
        $map = [];
        foreach ($rows as $r) {
            $map[$r['d']] = $r;
        }
        $labels = $cash = $bank = [];
        $start = new \DateTime($from);
        $end = new \DateTime($to);
        $days = (int) $start->diff($end)->days + 1;
        if ($days <= 62) {
            for ($d = clone $start; $d <= $end; $d->modify('+1 day')) {
                $k = $d->format('Y-m-d');
                $labels[] = $d->format($days > 31 ? 'd M' : 'j M');
                $cash[] = (float) ($map[$k]['c'] ?? 0);
                $bank[] = (float) ($map[$k]['b'] ?? 0);
            }
        } else {
            $bucket = [];
            foreach ($map as $k => $r) {
                $m = substr($k, 0, 7);
                $bucket[$m]['c'] = ($bucket[$m]['c'] ?? 0) + (float) $r['c'];
                $bucket[$m]['b'] = ($bucket[$m]['b'] ?? 0) + (float) $r['b'];
            }
            for ($d = new \DateTime($start->format('Y-m-01')); $d <= $end; $d->modify('+1 month')) {
                $m = $d->format('Y-m');
                $labels[] = $d->format('M y');
                $cash[] = (float) ($bucket[$m]['c'] ?? 0);
                $bank[] = (float) ($bucket[$m]['b'] ?? 0);
            }
        }

        return [
            'count' => (int) $t['n'], 'total' => (float) $t['total'], 'cash' => (float) $t['cash'],
            'transfer' => (float) $t['transfer'], 'pos' => (float) $t['pos'], 'other' => (float) $t['other'],
            'bank' => (float) $t['total'] - (float) $t['cash'],
            'series' => ['labels' => $labels, 'cash' => $cash, 'bank' => $bank],
        ];
    }

    public static function paymentCount(string $from, string $to, string $method = ''): int
    {
        $p = [$from, $to];
        $where = ' WHERE DATE(p.paid_at) BETWEEN ? AND ?' . self::methodWhere($method, $p);
        return (int) self::scalar('SELECT COUNT(*) FROM payments p' . $where, $p);
    }

    public static function paymentRows(string $from, string $to, string $method, int $limit, int $offset): array
    {
        $p = [$from, $to];
        $where = ' WHERE DATE(p.paid_at) BETWEEN ? AND ?' . self::methodWhere($method, $p);
        return self::all(
            'SELECT p.*, j.id AS job_id, j.invoice_number, c.full_name AS customer, u.name AS received_by_name
             FROM payments p JOIN jobs j ON j.id = p.job_id
             LEFT JOIN customers c ON c.id = j.customer_id LEFT JOIN users u ON u.id = p.received_by' . $where .
            ' ORDER BY p.paid_at DESC, p.id DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
            $p
        );
    }

    /**
     * Per day (or per month for long ranges): jobs, revenue (billed) and expenses. Drives the report charts.
     */
    public static function trend(string $from, string $to): array
    {
        $start = new \DateTime($from);
        $end = new \DateTime($to);
        $days = (int) $start->diff($end)->days + 1;
        $daily = $days <= 62;
        $fmt = $daily ? '%Y-%m-%d' : '%Y-%m';

        $jobs = array_column(self::all(
            "SELECT DATE_FORMAT(j.job_date,'$fmt') k, COUNT(*) c, SUM(j.total) t FROM jobs j WHERE " . self::BILLABLE . ' AND j.job_date BETWEEN ? AND ? GROUP BY k',
            [$from, $to]
        ), null, 'k');
        $exp = array_column(self::all(
            "SELECT DATE_FORMAT(expense_date,'$fmt') k, SUM(amount) t FROM expenses WHERE expense_date BETWEEN ? AND ? GROUP BY k",
            [$from, $to]
        ), 't', 'k');

        $out = ['labels' => [], 'jobs' => [], 'revenue' => [], 'expenses' => [], 'unit' => $daily ? 'day' : 'month'];
        $push = function (string $key, string $label) use (&$out, $jobs, $exp) {
            $out['labels'][] = $label;
            $out['jobs'][] = (int) ($jobs[$key]['c'] ?? 0);
            $out['revenue'][] = (float) ($jobs[$key]['t'] ?? 0);
            $out['expenses'][] = (float) ($exp[$key] ?? 0);
        };
        if ($daily) {
            for ($d = clone $start; $d <= $end; $d->modify('+1 day')) {
                $push($d->format('Y-m-d'), $d->format($days > 31 ? 'd M' : 'j M'));
            }
        } else {
            for ($d = new \DateTime($start->format('Y-m-01')); $d <= $end; $d->modify('+1 month')) {
                $push($d->format('Y-m'), $d->format('M y'));
            }
        }
        return $out;
    }

    /** Number of jobs per day (or per month for long ranges), zero-filled, for the bar chart. */
    public static function jobsSeries(string $from, string $to): array
    {
        $start = new \DateTime($from);
        $end = new \DateTime($to);
        $days = (int) $start->diff($end)->days + 1;
        $daily = $days <= 62;

        $fmt = $daily ? '%Y-%m-%d' : '%Y-%m';
        $rows = self::all(
            "SELECT DATE_FORMAT(j.job_date,'$fmt') k, COUNT(*) c FROM jobs j WHERE " . self::BILLABLE . ' AND j.job_date BETWEEN ? AND ? GROUP BY k',
            [$from, $to]
        );
        $map = array_column($rows, 'c', 'k');

        $labels = [];
        $values = [];
        if ($daily) {
            for ($d = clone $start; $d <= $end; $d->modify('+1 day')) {
                $labels[] = $d->format($days > 31 ? 'd M' : 'j M');
                $values[] = (int) ($map[$d->format('Y-m-d')] ?? 0);
            }
        } else {
            $d = new \DateTime($start->format('Y-m-01'));
            while ($d <= $end) {
                $labels[] = $d->format('M y');
                $values[] = (int) ($map[$d->format('Y-m')] ?? 0);
                $d->modify('+1 month');
            }
        }
        return ['labels' => $labels, 'values' => $values, 'unit' => $daily ? 'day' : 'month'];
    }

    public static function paymentsBetween(string $from, string $to): array
    {
        return self::all(
            'SELECT p.paid_at, j.invoice_number, c.full_name AS customer, p.method, p.reference, p.amount, u.name AS received_by
             FROM payments p JOIN jobs j ON j.id = p.job_id
             LEFT JOIN customers c ON c.id = j.customer_id LEFT JOIN users u ON u.id = p.received_by
             WHERE DATE(p.paid_at) BETWEEN ? AND ? ORDER BY p.paid_at',
            [$from, $to]
        );
    }
}
