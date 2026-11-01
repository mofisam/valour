<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Controller, Paginator, Session, Validator};
use App\Models\{ActivityLog, ClothingType, Customer, Job, Notification, Service, ServicePrice};
use App\Services\{InvoiceService, PricingService};

class JobController extends Controller
{
    public function index(): void
    {
        $filters = $this->request->only(['q', 'status', 'payment_status', 'from', 'to']);
        $view = (string) $this->request->input('view', '');
        if (!is_admin() && in_array($view, ['owing', 'pricing'], true)) {
            $view = '';
        }
        if (!is_admin()) {
            unset($filters['payment_status']);
        }
        $filters['draft']   = $view === 'drafts';
        $filters['owing']   = $view === 'owing';
        $filters['pricing'] = $view === 'pricing';
        $pager = new Paginator(Job::count($filters), (int) $this->request->input('page', 1), config('app.per_page'));

        $this->view('jobs/index', [
            'title'   => 'Jobs',
            'jobs'    => Job::paginate($filters, $pager->perPage, $pager->offset),
            'pager'   => $pager,
            'filters' => $filters,
            'view'    => $view,
            'counts'  => Job::tabCounts(),
        ]);
    }

    public function create(): void
    {
        $customer = null;
        if (is_admin() && ($cid = (int) $this->request->input('customer_id', 0))) {
            $customer = Customer::find($cid);
        }
        $this->formView('New job', null, $customer);
    }

    public function store(): void
    {
        $this->save(null);
    }

    public function show(string $id): void
    {
        $job = Job::full((int) $id) ?? abort(404);
        $this->view('jobs/show', ['title' => $job['invoice_number'] ?: 'Draft job #' . $job['id'], 'job' => $job]);
    }

    public function edit(string $id): void
    {
        $job = Job::full((int) $id) ?? abort(404);
        $this->guardEdit($job);
        $customer = $job['customer_id'] ? Customer::find((int) $job['customer_id']) : null;
        $this->formView('Edit job', $job, $customer);
    }

    public function update(string $id): void
    {
        $job = Job::find((int) $id) ?? abort(404);
        $this->guardEdit($job);
        $this->save((int) $id);
    }

    /** Staff may only change the status of a submitted job; they can edit drafts until they submit them. */
    private function guardEdit(array $job): void
    {
        if (!is_admin() && !$job['is_draft']) {
            abort(403, 'Submitted jobs can only be edited by the admin. You can update the status from the job page.');
        }
    }

    public function status(string $id): void
    {
        $job = Job::full((int) $id) ?? abort(404);
        $status = (string) $this->request->input('status');
        $allowed = $job['steps'];
        if (is_admin()) {
            $allowed[] = 'cancelled';
        }
        if (!in_array($status, $allowed, true)) {
            $this->flash('error', 'That status is not part of this service\'s process.');
            $this->redirect("/jobs/$id");
        }
        if ($job['is_draft']) {
            $this->flash('error', 'Submit the draft before changing its status.');
            $this->redirect("/jobs/$id");
        }
        Job::setStatus((int) $id, $status);
        ActivityLog::log('job.status', 'job', (int) $id, ($job['invoice_number'] ?: 'Job #' . $id) . ': ' . (JOB_LABELS[$job['status']] ?? $job['status']) . ' → ' . (JOB_LABELS[$status] ?? $status));
        $this->flash('success', 'Status updated to ' . strtolower(JOB_LABELS[$status] ?? $status) . '.');
        back("/jobs/$id");
    }

    /** Submit a draft: gives it an invoice number and, if it has no price yet, tells the admin. */
    public function finalize(string $id): void
    {
        $job = Job::find((int) $id) ?? abort(404);
        $number = InvoiceService::finalize((int) $id);
        ActivityLog::log('job.finalized', 'job', (int) $id, "Invoice $number created");
        if ($job['needs_pricing']) {
            $this->notifyPricing((int) $id);
        }
        $this->flash('success', "Job submitted as $number." . ($job['needs_pricing'] ? ' The admin has been asked to set the price.' : ''));
        $this->redirect("/jobs/$id");
    }

    /** Admin: fill any unpriced lines from the service price list, then mark the job as priced. */
    public function price(string $id): void
    {
        $job = Job::full((int) $id) ?? abort(404);
        $service = $job['service_id'] ? Service::find((int) $job['service_id']) : null;

        $input = [
            'items' => array_map(fn($i) => [
                'clothing_type_id' => $i['clothing_type_id'],
                'color' => $i['color'], 'length' => $i['length'], 'quantity' => $i['quantity'],
                'price_per_unit' => (float) $i['price_per_unit'] > 0 ? $i['price_per_unit'] : '',   // blank = use price list
            ], $job['items']),
            'total_kg'       => $job['total_kg'],
            'price_per_kg'   => (float) $job['price_per_kg'] > 0 ? $job['price_per_kg'] : '',
            'duration_hours' => intdiv((int) $job['duration_minutes'], 60),
            'duration_mins'  => (int) $job['duration_minutes'] % 60,
            'price_per_hour' => (float) $job['price_per_hour'] > 0 ? $job['price_per_hour'] : '',
            'discount_type'  => $job['discount_type'],
            'discount_value' => $job['discount_value'],
        ];
        $built = PricingService::build($input, $service, true, $job);
        Job::saveWithItems($built['job'], $built['items'], (int) $id);
        Notification::resolveLink("/jobs/$id");
        ActivityLog::log('job.priced', 'job', (int) $id, ($job['invoice_number'] ?: 'Job #' . $id) . ' priced at ' . money($built['job']['total']));

        if ($built['job']['total'] <= 0) {
            $this->flash('warning', 'No price was found for this service and these items. Add prices under Services & prices, or edit the job and type the prices in.');
        } else {
            $this->flash('success', 'Job priced at ' . money($built['job']['total']) . ' using the service price list.');
        }
        $this->redirect("/jobs/$id");
    }

    public function destroy(string $id): void
    {
        $job = Job::find((int) $id) ?? abort(404);
        Job::delete((int) $id);
        Notification::resolveLink("/jobs/$id");
        ActivityLog::log('job.deleted', 'job', (int) $id, ($job['invoice_number'] ?: 'Draft #' . $id) . ' (' . money($job['total']) . ')');
        $this->flash('success', 'Job deleted.');
        $this->redirect('/jobs');
    }

    /* ---------- internals ---------- */

    private function formView(string $title, ?array $job, ?array $customer): void
    {
        $services = Service::active();
        $types = ClothingType::active();
        $this->view('jobs/form', [
            'title'         => $title,
            'job'           => $job,
            'customer'      => $customer,
            'services'      => array_map(fn($s) => $s + ['steps' => Service::workflowOf($s)], $services),
            'clothingTypes' => $types,
            'prices'        => is_admin() ? ServicePrice::effectiveMap(array_column($services, 'id'), $types) : [],
        ]);
    }

    private function save(?int $id): void
    {
        $in = $this->request->all();
        $errors = Validator::validate($in, [
            'customer_id'    => 'required|integer',
            'service_id'     => 'required|integer',
            'job_date'       => 'required|date',
            'due_date'       => 'date',
            'discount_value' => 'numeric|min:0',
        ], ['customer_id' => 'Customer', 'service_id' => 'Service']);

        $service = !isset($errors['service_id']) ? Service::find((int) $in['service_id']) : null;
        if (!isset($errors['service_id']) && !$service) {
            $errors['service_id'] = 'Choose a valid service.';
        }
        $customer = !isset($errors['customer_id']) ? Customer::find((int) $in['customer_id']) : null;
        if (!isset($errors['customer_id']) && !$customer) {
            $errors['customer_id'] = 'Choose a customer from the list.';
        }

        $existing = $id ? Job::full($id) : null;
        $built = PricingService::build($in, $service, is_admin(), $existing);
        $errors += $built['errors'];

        if ($errors) {
            Session::flashErrors($errors, $in);
            back($id ? "/jobs/$id/edit" : '/jobs/create');
        }

        $isDraft = isset($in['save_draft']);
        $fields = $built['job'] + [
            'customer_id' => (int) $in['customer_id'],
            'service_id'  => (int) $in['service_id'],
            'job_date'    => $in['job_date'],
            'due_date'    => ($in['due_date'] ?? '') !== '' ? $in['due_date'] : null,
            'notes'       => ($in['notes'] ?? '') !== '' ? $in['notes'] : null,
        ];
        $summary = ($service['name'] ?? 'Job') . ' for ' . ($customer['full_name'] ?? '—');

        if ($id === null) {
            $fields['created_by'] = Auth::id();
            $fields['is_draft'] = $isDraft ? 1 : 0;
            $id = Job::saveWithItems($fields, $built['items']);
            $number = null;
            if (!$isDraft) {
                $number = InvoiceService::finalize($id);
                if ($fields['needs_pricing']) {
                    $this->notifyPricing($id);
                }
            }
            ActivityLog::log('job.created', 'job', $id, ($number ?: 'Draft #' . $id) . ': ' . $summary . ($fields['needs_pricing'] ? ' (price not set)' : ', ' . money($fields['total'])));
            $this->flash('success', $isDraft ? 'Draft saved.' : ($fields['needs_pricing'] ? 'Job submitted. The admin has been asked to set the price.' : 'Job created and invoice generated.'));
        } else {
            $before = (float) ($existing['total'] ?? 0);
            $wasDraft = (bool) ($existing['is_draft'] ?? false);
            Job::saveWithItems($fields, $built['items'], $id);

            // Keep the status valid for the (possibly new) service's process
            $steps = Service::workflowOf($service);
            $current = (string) Job::find($id)['status'];
            $statusNote = '';
            if ($current !== 'cancelled' && !in_array($current, $steps, true)) {
                Job::setStatus($id, 'pending');
                $statusNote = '; status reset to Pending because the service process changed';
            }

            if (!$isDraft && $wasDraft) {
                InvoiceService::finalize($id);
                if ($fields['needs_pricing']) {
                    $this->notifyPricing($id);
                }
            }
            if (is_admin()) {
                Notification::resolveLink("/jobs/$id");
            }
            $label = ($existing['invoice_number'] ?: 'Draft #' . $id);
            $change = abs($before - $fields['total']) > 0.004 ? ', total ' . money($before) . ' → ' . money($fields['total']) : '';
            ActivityLog::log('job.updated', 'job', $id, $label . ': ' . $summary . $change . $statusNote);
            $this->flash('success', 'Job updated.');
        }
        $this->redirect("/jobs/$id");
    }

    private function notifyPricing(int $jobId): void
    {
        $job = Job::full($jobId);
        if (!$job) {
            return;
        }
        Notification::notifyAdmins(
            'pricing',
            ($job['invoice_number'] ?: 'A job') . ' needs a price',
            ($job['created_by_name'] ?? 'Staff') . ' submitted ' . ($job['service_name'] ?? 'a job') . ' for ' . ($job['customer_name'] ?? 'a customer') . '. Open it to set the price.',
            "/jobs/$jobId"
        );
    }
}
