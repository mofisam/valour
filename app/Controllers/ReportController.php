<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ReportService;

class ReportController extends Controller
{
    public function index(): void
    {
        [$from, $to] = $this->range();
        $this->view('reports/index', [
            'title'   => 'Reports',
            'from'    => $from,
            'to'      => $to,
            'summary' => ReportService::summary($from, $to),
        ]);
    }

    /** CSV of all payments in the range (opens in Excel). */
    public function export(): void
    {
        [$from, $to] = $this->range();
        $rows = ReportService::paymentsBetween($from, $to);

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=payments_{$from}_to_{$to}.csv");
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
        fputcsv($out, ['Paid at', 'Invoice', 'Customer', 'Method', 'Reference', 'Amount', 'Received by']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['paid_at'], $r['invoice_number'], $r['customer'], $r['method'], $r['reference'], $r['amount'], $r['received_by']]);
        }
        fclose($out);
        exit;
    }

    private function range(): array
    {
        $valid = fn($d) => is_string($d) && \DateTime::createFromFormat('Y-m-d', $d) !== false;
        $from = $this->request->input('from');
        $to   = $this->request->input('to');
        $from = $valid($from) ? $from : date('Y-m-01');
        $to   = $valid($to) ? $to : date('Y-m-d');
        return $from <= $to ? [$from, $to] : [$to, $from];
    }
}
