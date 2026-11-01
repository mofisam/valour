<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Controller};
use App\Models\{ActivityLog, Job, Payment};

class PaymentController extends Controller
{
    public function store(string $id): void
    {
        $job = Job::find((int) $id) ?? abort(404);
        if ($job['is_draft']) {
            $this->flash('error', 'Finalize the draft before taking payment.');
            $this->redirect("/jobs/$id");
        }

        $method = (string) $this->request->input('method', 'cash');
        $amount = round((float) $this->request->input('amount', 0), 2);
        if (!in_array($method, PAYMENT_METHODS, true)) {
            $method = 'other';
        }

        try {
            $pid = Payment::add((int) $id, $amount, $method, $this->request->input('reference'), null, Auth::id());
            ActivityLog::log('payment.added', 'job', (int) $id, money($amount) . ' by ' . $method . ' on ' . ($job['invoice_number'] ?: "job #$id") . ($this->request->input('reference') ? ' (ref ' . $this->request->input('reference') . ')' : ''));
            $this->flash('success', 'Payment of ' . money($amount) . ' recorded.');
        } catch (\RuntimeException $e) {
            $this->flash('error', $e->getMessage());
        }
        $this->redirect("/jobs/$id");
    }

    public function destroy(string $id): void
    {
        $p = Payment::find((int) $id) ?? abort(404);
        $jobId = Payment::remove((int) $id);
        $j = \App\Models\Job::find((int) $jobId);
        ActivityLog::log('payment.deleted', 'job', (int) $jobId, money($p['amount']) . ' (' . $p['method'] . ') removed from ' . ($j['invoice_number'] ?? "job #$jobId"));
        $this->flash('success', 'Payment removed.');
        $this->redirect("/jobs/$jobId");
    }
}
