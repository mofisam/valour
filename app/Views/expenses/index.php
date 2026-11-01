<?php $f = $filters; ?>
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <h1 class="font-display text-2xl font-bold">Expenses</h1>
    <?php if (is_admin()): ?><a class="btn-ghost" href="<?= url('/expense-categories') ?>">Manage categories</a><?php endif; ?>
</div>
<div class="grid gap-6 lg:grid-cols-3">
    <section class="card card-pad lg:col-span-1">
        <h2 class="font-display text-lg font-bold">Add an expense</h2>
        <form method="post" action="<?= url('/expenses') ?>" class="mt-3 space-y-3">
            <?= csrf_field() ?>
            <div>
                <label class="label" for="expense_date">Date</label>
                <input class="input" type="date" id="expense_date" name="expense_date" value="<?= e(old('expense_date', date('Y-m-d'))) ?>" required>
            </div>
            <div>
                <label class="label" for="category_id">Category</label>
                <select class="input" id="category_id" name="category_id">
                    <option value="">Uncategorised</option>
                    <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label" for="amount">Amount</label>
                <input class="input <?= error('amount') ? 'input-error' : '' ?>" type="number" step="0.01" min="0.01" id="amount" name="amount" value="<?= e(old('amount')) ?>" required>
                <?php if ($m = error('amount')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="label" for="description">What was it for?</label>
                <textarea class="input" rows="2" id="description" name="description"><?= e(old('description')) ?></textarea>
            </div>
            <button class="btn-primary w-full" type="submit">Save expense</button>
        </form>
    </section>

    <section class="card lg:col-span-2">
        <form method="get" class="flex flex-wrap items-end gap-3 border-b border-line p-4">
            <div><label class="label" for="from">From</label><input class="input" type="date" id="from" name="from" value="<?= e($f['from']) ?>"></div>
            <div><label class="label" for="to">To</label><input class="input" type="date" id="to" name="to" value="<?= e($f['to']) ?>"></div>
            <div>
                <label class="label" for="fcat">Category</label>
                <select class="input" id="fcat" name="category_id">
                    <option value="">All</option>
                    <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (string) $f['category_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <button class="btn-primary" type="submit">Filter</button>
            <div class="ml-auto text-right"><div class="text-xs text-muted">Total in range</div><div class="num font-display text-lg font-bold"><?= money($total) ?></div></div>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>By</th><th class="text-right">Amount</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($expenses as $x): ?>
                    <tr>
                        <td><?= e(fdate($x['expense_date'])) ?></td>
                        <td><?= e($x['category_name'] ?? '—') ?></td>
                        <td class="max-w-xs truncate"><?= e($x['description'] ?: '—') ?></td>
                        <td><?= e($x['created_by_name'] ?? '—') ?></td>
                        <td class="num text-right"><?= money($x['amount']) ?></td>
                        <td class="whitespace-nowrap text-right">
                            <a class="btn-ghost btn-sm" href="<?= url('/expenses/' . $x['id'] . '/edit') ?>">Edit</a>
                            <?php if (is_admin()): ?>
                                <form class="inline" method="post" action="<?= url('/expenses/' . $x['id'] . '/delete') ?>" data-confirm="Delete this expense?"><?= csrf_field() ?><button class="btn-danger btn-sm" type="submit">Delete</button></form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$expenses): ?><tr><td colspan="6" class="py-8 text-center text-muted">No expenses in this range.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php \App\Core\View::partial('partials/pagination', ['pager' => $pager]); ?>
    </section>
</div>
