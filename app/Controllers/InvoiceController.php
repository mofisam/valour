<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Controller, View};
use App\Models\Job;

class InvoiceController extends Controller
{
    public function show(string $id): void
    {
        [$job, $template] = $this->load((int) $id);
        View::render("invoices/templates/$template", $this->data($job), 'print');
    }

    /** PDF via dompdf if installed (composer require dompdf/dompdf); otherwise fall back to the print view. */
    public function pdf(string $id): void
    {
        [$job, $template] = $this->load((int) $id);

        if (!class_exists(\Dompdf\Dompdf::class)) {
            $this->flash('info', 'PDF export needs dompdf (composer require dompdf/dompdf). Use Print → Save as PDF for now.');
            $this->redirect("/invoices/$id");
        }

        ob_start();
        View::render("invoices/templates/$template", $this->data($job) + ['forPdf' => true], 'print');
        $html = (string) ob_get_clean();

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();
        $dompdf->stream(($job['invoice_number'] ?: 'invoice') . '.pdf', ['Attachment' => true]);
    }

    private function load(int $id): array
    {
        $job = Job::full($id) ?? abort(404);
        if (!$job['invoice_number']) {
            $this->flash('info', 'This draft has no invoice yet. Finalize it first.');
            $this->redirect("/jobs/$id");
        }
        if ($job['needs_pricing']) {
            $this->flash('info', 'This job is waiting for the admin to set the price, so there is no invoice to show yet.');
            $this->redirect("/jobs/$id");
        }
        $template = (string) setting('invoice_template', 'classic');
        if (!in_array($template, ['classic', 'modern'], true)) {
            $template = 'classic';
        }
        return [$job, $template];
    }

    private function data(array $job): array
    {
        return [
            'title' => 'Invoice ' . $job['invoice_number'],
            'job'   => $job,
            'biz'   => [
                'name'    => company_name(),
                'logo'    => setting('business_logo_url'),
                'address' => setting('business_address'),
                'phone'   => setting('business_phone'),
                'email'   => setting('business_email'),
                'bank'    => setting('bank_name'),
                'acc_no'  => setting('bank_account_number'),
                'acc_name'=> setting('bank_account_name'),
                'footer'  => setting('invoice_footer'),
            ],
        ];
    }
}
