<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Controller, Paginator};
use App\Services\ReportService;

/** Admin-only detail page behind the "Bank / Cash received" card. */
class CashController extends Controller
{
    public function index(): void
    {
        $valid = fn($d) => is_string($d) && \DateTime::createFromFormat('Y-m-d', $d) !== false;
        $from = $this->request->input('from');
        $to = $this->request->input('to');
        $from = $valid($from) ? $from : date('Y-m-01');     // the month starts on day 1
        $to = $valid($to) ? $to : date('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $method = (string) $this->request->input('method', '');
        if (!in_array($method, ['', 'cash', 'bank', 'transfer', 'pos', 'other'], true)) {
            $method = '';
        }

        $pager = new Paginator(ReportService::paymentCount($from, $to, $method), (int) $this->request->input('page', 1), 25);
        $this->view('cash/index', [
            'title'   => 'Bank & Cash',
            'from'    => $from,
            'to'      => $to,
            'method'  => $method,
            'sum'     => ReportService::cashSummary($from, $to, $method),
            'rows'    => ReportService::paymentRows($from, $to, $method, $pager->perPage, $pager->offset),
            'pager'   => $pager,
        ]);
    }
}
