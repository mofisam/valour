<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Controller, Paginator};
use App\Models\{ActivityLog, Customer, Job};

class CustomerController extends Controller
{
    private const RULES = [
        'full_name'    => 'required|max_length:255',
        'phone_number' => 'max_length:30',
        'email'        => 'email|max_length:255',
        'birthday'     => 'date',
    ];

    public function index(): void
    {
        $q = (string) $this->request->input('q', '');
        $owing = $this->request->input('owing') === '1';
        $pager = new Paginator(Customer::count($q, $owing), (int) $this->request->input('page', 1), config('app.per_page'));
        $this->view('customers/index', [
            'title' => 'Customers',
            'customers' => Customer::paginate($q, $pager->perPage, $pager->offset, $owing),
            'pager' => $pager,
            'q' => $q,
            'owing' => $owing,
            'owed' => Customer::owingSummary(),
        ]);
    }

    public function create(): void
    {
        $this->view('customers/form', ['title' => 'New customer', 'customer' => null]);
    }

    public function store(): void
    {
        $this->validate(self::RULES);
        $id = Customer::insert($this->fields());
        ActivityLog::log('customer.created', 'customer', $id, (string) $this->request->input('full_name') . ($this->request->input('phone_number') ? ', ' . $this->request->input('phone_number') : ''));
        $this->flash('success', 'Customer added.');
        $this->redirect($this->request->input('then') === 'job' ? "/jobs/create?customer_id=$id" : "/customers/$id");
    }

    public function show(string $id): void
    {
        $customer = Customer::withStats((int) $id) ?? abort(404);
        $this->view('customers/show', [
            'title' => $customer['full_name'],
            'customer' => $customer,
            'jobs' => Job::paginate(['customer_id' => (int) $id], 50, 0),
        ]);
    }

    public function edit(string $id): void
    {
        $customer = Customer::find((int) $id) ?? abort(404);
        $this->view('customers/form', ['title' => 'Edit customer', 'customer' => $customer]);
    }

    public function update(string $id): void
    {
        $before = Customer::find((int) $id) ?? abort(404);
        $this->validate(self::RULES);
        $fields = $this->fields();
        Customer::update((int) $id, $fields);
        $changed = [];
        foreach (['full_name' => 'name', 'phone_number' => 'phone', 'email' => 'email', 'address' => 'address', 'birthday' => 'birthday'] as $k => $label) {
            if ((string) ($before[$k] ?? '') !== (string) ($fields[$k] ?? '')) {
                $changed[] = $label;
            }
        }
        ActivityLog::log('customer.updated', 'customer', (int) $id, $fields['full_name'] . ($changed ? ' (changed: ' . implode(', ', $changed) . ')' : ' (no changes)'));
        $this->flash('success', 'Customer updated.');
        $this->redirect("/customers/$id");
    }

    public function destroy(string $id): void
    {
        $c = Customer::find((int) $id) ?? abort(404);
        Customer::delete((int) $id); // soft delete: jobs and invoices stay intact
        ActivityLog::log('customer.deleted', 'customer', (int) $id, $c['full_name']);
        $this->flash('success', 'Customer removed.');
        $this->redirect('/customers');
    }

    private function fields(): array
    {
        $r = $this->request;
        return [
            'full_name'    => $r->input('full_name'),
            'phone_number' => $r->input('phone_number') ?: null,
            'email'        => $r->input('email') ?: null,
            'address'      => $r->input('address') ?: null,
            'birthday'     => $r->input('birthday') ?: null,
            'notes'        => $r->input('notes') ?: null,
        ];
    }
}
