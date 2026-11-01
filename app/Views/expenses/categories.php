<div class="mb-5">
    <a href="<?= url('/expenses') ?>" class="text-sm text-link hover:underline">Back to expenses</a>
    <h1 class="font-display text-2xl font-bold">Expense categories</h1>
    <p class="mt-1 max-w-2xl text-sm text-muted">Tick <strong>Direct cost</strong> for costs of delivering the service (detergent, water, power, generator fuel).
        These are subtracted from revenue to give <strong>gross profit</strong>. All other categories are operating expenses.</p>
</div>

<section class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Category</th><th>Direct cost</th><th class="text-right">Expenses</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td><form method="post" action="<?= url('/expense-categories/' . $c['id']) ?>" id="cat-<?= $c['id'] ?>"><?= csrf_field() ?></form>
                        <input form="cat-<?= $c['id'] ?>" class="input" name="name" value="<?= e($c['name']) ?>" aria-label="Category name"></td>
                    <td><label class="inline-flex items-center gap-2 text-sm"><input form="cat-<?= $c['id'] ?>" type="checkbox" name="is_direct_cost" value="1" <?= $c['is_direct_cost'] ? 'checked' : '' ?>> Cost of service</label></td>
                    <td class="num text-right"><?= (int) $c['uses'] ?></td>
                    <td class="whitespace-nowrap text-right">
                        <button form="cat-<?= $c['id'] ?>" class="btn-ghost btn-sm" type="submit">Save</button>
                        <?php if (!$c['uses']): ?>
                            <form class="inline" method="post" action="<?= url('/expense-categories/' . $c['id'] . '/delete') ?>" data-confirm="Delete this category?"><?= csrf_field() ?><button class="btn-danger btn-sm" type="submit">Delete</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="post" action="<?= url('/expense-categories') ?>" class="flex flex-wrap items-center gap-3 border-t border-line p-5">
        <?= csrf_field() ?>
        <input class="input max-w-xs" name="name" placeholder="New category name" required aria-label="New category name">
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_direct_cost" value="1"> Direct cost</label>
        <button class="btn-primary" type="submit">Add category</button>
    </form>
</section>
