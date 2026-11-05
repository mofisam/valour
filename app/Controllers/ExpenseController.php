<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Controller, Paginator};
use App\Models\{ActivityLog, Expense};

class ExpenseController extends Controller
{
    private const RULES = [
        'expense_date' => 'required|date',
        'amount'       => 'required|numeric|min:0.01',
        'category_id'  => 'integer',
        'paid_from'    => 'required|in:cash,bank',
    ];

    public function index(): void
    {
        if (!is_admin()) {
            $this->staffIndex();
        }
        $filters = [
            'from'        => $this->request->input('from', date('Y-m-01')),
            'to'          => $this->request->input('to', date('Y-m-d')),
            'category_id' => $this->request->input('category_id', ''),
            'paid_from'   => in_array($this->request->input('paid_from', ''), ['cash', 'bank'], true) ? $this->request->input('paid_from') : '',
        ];
        $pager = new Paginator(Expense::count($filters), (int) $this->request->input('page', 1), config('app.per_page'));

        $this->view('expenses/index', [
            'title'      => 'Expenses',
            'expenses'   => Expense::paginate($filters, $pager->perPage, $pager->offset),
            'pager'      => $pager,
            'filters'    => $filters,
            'total'      => Expense::totalFor($filters),
            'bySource'   => Expense::totalsBySource($filters),
            'categories' => Expense::categories(),
        ]);
    }

    public function store(): void
    {
        $this->validate(self::RULES);
        $id = Expense::insert($this->fields() + ['created_by' => Auth::id()]);
        ActivityLog::log('expense.created', 'expense', $id, $this->describe($this->fields()));
        $this->flash('success', 'Expense recorded.');
        $this->redirect('/expenses');
    }

    public function edit(string $id): void
    {
        $expense = Expense::find((int) $id) ?? abort(404);
        $this->view('expenses/edit', ['title' => 'Edit expense', 'expense' => $expense, 'categories' => Expense::categories()]);
    }

    public function update(string $id): void
    {
        $old = Expense::find((int) $id) ?? abort(404);
        $this->validate(self::RULES);
        $new = $this->fields();
        Expense::update((int) $id, $new);
        ActivityLog::log('expense.updated', 'expense', (int) $id, 'Was ' . $this->describe($old) . '; now ' . $this->describe($new));
        $this->flash('success', 'Expense updated.');
        $this->redirect('/expenses');
    }

    public function destroy(string $id): void
    {
        $old = Expense::find((int) $id) ?? abort(404);
        Expense::delete((int) $id);
        ActivityLog::log('expense.deleted', 'expense', (int) $id, $this->describe($old));
        $this->flash('success', 'Expense deleted.');
        $this->redirect('/expenses');
    }

    private function fields(): array
    {
        $r = $this->request;
        return [
            'expense_date' => (!is_admin() && (string) $r->input('expense_date') > date('Y-m-d')) ? date('Y-m-d') : $r->input('expense_date'),
            'category_id'  => $r->input('category_id') ?: null,
            'description'  => $r->input('description') ?: null,
            'amount'       => round((float) $r->input('amount'), 2),
            'paid_from'    => $r->input('paid_from') === 'bank' ? 'bank' : 'cash',
        ];
    }

    /** "₦5,000.00 · Water · borehole refill" for the activity log */
    private function describe(array $e): string
    {
        $cat = !empty($e['category_id']) ? (string) \App\Core\Model::scalar('SELECT name FROM expense_categories WHERE id = ?', [(int) $e['category_id']]) : 'Uncategorised';
        return money($e['amount']) . ' from ' . (($e['paid_from'] ?? 'cash') === 'bank' ? 'bank' : 'cash') . ' · ' . $cat . (!empty($e['description']) ? ' · ' . mb_substr((string) $e['description'], 0, 60) : '') . ' · ' . ($e['expense_date'] ?? '');
    }

    /** Staff see a simple "add expense" page with what they recorded themselves this month. No totals, no editing. */
    private function staffIndex(): never
    {
        $filters = ['from' => date('Y-m-01'), 'to' => date('Y-m-d'), 'created_by' => Auth::id()];
        $pager = new Paginator(Expense::count($filters), (int) $this->request->input('page', 1), 15);
        $this->view('expenses/staff', [
            'title'      => 'Expenses',
            'expenses'   => Expense::paginate($filters, $pager->perPage, $pager->offset),
            'pager'      => $pager,
            'categories' => Expense::categories(),
        ]);
        exit;
    }
}
