<div class="mb-5">
    <h1 class="flex items-center gap-2 font-display text-2xl font-bold"><?= icon('credit-card', 'h-6 w-6 text-sea') ?> Record an expense</h1>
    <p class="text-sm text-muted">Add money the business spent. The admin can see every expense. If you make a mistake, tell the admin so they can correct it.</p>
</div>

<div class="grid gap-6 lg:grid-cols-5">
    <section class="card card-pad h-fit lg:col-span-2">
        <form method="post" action="<?= url('/expenses') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="label" for="expense_date">Date</label>
                <input class="input" type="date" id="expense_date" name="expense_date" max="<?= date('Y-m-d') ?>" value="<?= e(old('expense_date', date('Y-m-d'))) ?>" required>
            </div>
            <div>
                <label class="label" for="category_id">What kind of expense?</label>
                <select class="input" id="category_id" name="category_id">
                    <option value="">Choose…</option>
                    <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (string) old('category_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <fieldset>
                <legend class="label">Paid from</legend>
                <div class="grid grid-cols-2 gap-2">
                    <?php foreach (['cash' => ['Cash', 'banknote'], 'bank' => ['Bank', 'bank']] as $k => [$l, $ic]): ?>
                        <label class="flex cursor-pointer items-center gap-2 rounded-md border border-line px-3 py-2 text-sm has-[:checked]:border-sea has-[:checked]:bg-sea/10">
                            <input type="radio" name="paid_from" value="<?= $k ?>" <?= old('paid_from', 'cash') === $k ? 'checked' : '' ?> required>
                            <?= icon($ic, 'h-4 w-4 text-muted') ?> <?= $l ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <div>
                <label class="label" for="amount">Amount</label>
                <input class="input <?= error('amount') ? 'input-error' : '' ?>" type="number" step="0.01" min="0.01" id="amount" name="amount" value="<?= e(old('amount')) ?>" required>
                <?php if ($m = error('amount')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="label" for="description">What was it for?</label>
                <textarea class="input" rows="2" id="description" name="description" placeholder="e.g. 2 bags of detergent from the market"><?= e(old('description')) ?></textarea>
            </div>
            <button class="btn-primary w-full" type="submit">Save expense</button>
        </form>
    </section>

    <section class="card lg:col-span-3">
        <h2 class="panel-title px-5 py-4">What you recorded this month</h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Paid from</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                <?php foreach ($expenses as $x): ?>
                    <tr>
                        <td class="whitespace-nowrap"><?= e(fdate($x['expense_date'])) ?></td>
                        <td><?= e($x['category_name'] ?? '—') ?></td>
                        <td class="max-w-[14rem] truncate"><?= e($x['description'] ?: '—') ?></td>
                        <td><span class="badge <?= $x['paid_from'] === 'bank' ? 'badge-washing' : 'badge-paid' ?>"><?= $x['paid_from'] === 'bank' ? 'Bank' : 'Cash' ?></span></td>
                        <td class="num text-right"><?= money($x['amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$expenses): ?><tr><td colspan="5" class="py-10 text-center text-muted">You haven't recorded any expenses this month.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php \App\Core\View::partial('partials/pagination', ['pager' => $pager]); ?>
    </section>
</div>
