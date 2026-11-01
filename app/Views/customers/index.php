<?php \App\Core\View::script('customers.js'); ?>
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="font-display text-2xl font-bold">Customers</h1>
        <p class="text-sm text-muted">
            <?php if ($owed['customers']): ?>
                Receivables: <span class="font-semibold text-red-700 dark:text-red-300"><?= money($owed['total']) ?></span> from <?= (int) $owed['customers'] ?> customer<?= $owed['customers'] === 1 ? '' : 's' ?>.
            <?php else: ?>No outstanding receivables.<?php endif; ?>
        </p>
    </div>
    <?php if (is_admin()): ?><a href="<?= url('/customers/create') ?>" class="btn-primary">Add customer</a><?php endif; ?>
</div>

<div class="mb-4 flex flex-wrap gap-2">
    <a class="tab <?= !$owing ? 'tab-active' : '' ?>" href="<?= url('/customers' . ($q !== '' ? '?q=' . urlencode($q) : '')) ?>">All customers</a>
    <a class="tab <?= $owing ? 'tab-active' : '' ?>" href="<?= url('/customers?owing=1' . ($q !== '' ? '&q=' . urlencode($q) : '')) ?>">Receivables <span class="num rounded-full bg-red-600 px-1.5 text-xs text-white"><?= (int) $owed['customers'] ?></span></a>
</div>

<div class="card">
    <form method="get" class="border-b border-line p-4">
        <?php if ($owing): ?><input type="hidden" name="owing" value="1"><?php endif; ?>
        <input type="search" name="q" value="<?= e($q) ?>" data-live-search class="input max-w-sm" placeholder="Search by name or phone" aria-label="Search customers">
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Name</th><th>Phone</th><th class="text-right">Jobs</th><th class="text-right">Total paid</th><th class="text-right">Receivable</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($customers as $c): ?>
                <tr>
                    <td>
                        <a class="font-medium text-link hover:underline" href="<?= url('/customers/' . $c['id']) ?>"><?= e($c['full_name']) ?></a>
                        <?php if ($c['birthday'] && date('m-d', strtotime($c['birthday'])) === date('m-d')): ?><span class="ml-1 text-xs" title="Birthday today">🎂</span><?php endif; ?>
                    </td>
                    <td><?= e($c['phone_number'] ?: '—') ?></td>
                    <td class="num text-right"><?= (int) $c['job_count'] ?></td>
                    <td class="num text-right"><?= money($c['total_paid']) ?></td>
                    <td class="num text-right font-semibold <?= $c['outstanding'] > 0 ? 'text-red-700 dark:text-red-300' : 'text-muted' ?>"><?= $c['outstanding'] > 0 ? money($c['outstanding']) : '—' ?></td>
                    <td class="text-right"><a class="btn-ghost btn-sm" href="<?= url('/jobs/create?customer_id=' . $c['id']) ?>">New job</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$customers): ?>
                <tr><td colspan="6" class="py-10 text-center text-muted"><?= $q ? 'No customers match “' . e($q) . '”.' : ($owing ? 'No outstanding receivables.' : (is_admin() ? 'No customers yet. Add your first customer.' : 'No customers yet. Ask the admin to add them.')) ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php \App\Core\View::partial('partials/pagination', ['pager' => $pager]); ?>
</div>
