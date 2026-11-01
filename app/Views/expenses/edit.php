<div class="mx-auto max-w-lg">
    <h1 class="mb-5 font-display text-2xl font-bold">Edit expense</h1>
    <form method="post" action="<?= url('/expenses/' . $expense['id']) ?>" class="card card-pad space-y-4">
        <?= csrf_field() ?>
        <div><label class="label" for="expense_date">Date</label>
            <input class="input" type="date" id="expense_date" name="expense_date" value="<?= e(old('expense_date', $expense['expense_date'])) ?>" required></div>
        <div><label class="label" for="category_id">Category</label>
            <select class="input" id="category_id" name="category_id">
                <option value="">Uncategorised</option>
                <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (string) old('category_id', $expense['category_id']) === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
            </select></div>
        <div><label class="label" for="amount">Amount</label>
            <input class="input" type="number" step="0.01" min="0.01" id="amount" name="amount" value="<?= e(old('amount', $expense['amount'])) ?>" required></div>
        <div><label class="label" for="description">What was it for?</label>
            <textarea class="input" rows="3" id="description" name="description"><?= e(old('description', $expense['description'])) ?></textarea></div>
        <div class="flex gap-3"><button class="btn-primary" type="submit">Save changes</button><a class="btn-ghost" href="<?= url('/expenses') ?>">Cancel</a></div>
    </form>
</div>
