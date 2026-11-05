<?php
$f = $filters;
$admin = is_admin();

// Tabs: All, each process status that is in use, then Needs price / Unpaid (admin) and Drafts
$tabs = [['', 'All', $counts['all'], fn() => $view === '' && empty($f['status'])]];
foreach (job_steps() as $st => $step) {
    if (($counts[$st] ?? 0) > 0 || $step['locked'] || ($f['status'] ?? '') === $st) {
        $tabs[] = [$st, $step['label'], $counts[$st] ?? 0, fn() => ($f['status'] ?? '') === $st];
    }
}
if ($admin) {
    $tabs[] = ['pricing', 'Needs price', $counts['pricing'], fn() => $view === 'pricing'];
    $tabs[] = ['owing', 'Unpaid', $counts['owing'], fn() => $view === 'owing'];
}
$tabs[] = ['drafts', 'Drafts', $counts['drafts'], fn() => $view === 'drafts'];
?>
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <h1 class="font-display text-2xl font-bold">Jobs</h1>
    <a href="<?= url('/jobs/create') ?>" class="btn-primary">Create a job</a>
</div>

<nav class="mb-4 flex gap-2 overflow-x-auto pb-1" aria-label="Job filters">
    <?php foreach ($tabs as [$key, $label, $n, $active]):
        $href = in_array($key, ['owing', 'drafts', 'pricing'], true) ? '/jobs?view=' . $key : ($key === '' ? '/jobs' : '/jobs?status=' . $key); ?>
        <a class="tab <?= $active() ? 'tab-active' : '' ?>" href="<?= url($href) ?>"><?= e($label) ?> <span class="num text-xs opacity-70"><?= (int) $n ?></span></a>
    <?php endforeach; ?>
</nav>

<?php \App\Core\View::script('status-menu.js'); ?>
<div class="card">
    <form method="get" class="grid gap-3 border-b border-line p-4 sm:grid-cols-2 <?= $admin ? 'lg:grid-cols-6' : 'lg:grid-cols-5' ?>">
        <?php if ($view !== ''): ?><input type="hidden" name="view" value="<?= e($view) ?>"><?php endif; ?>
        <input class="input lg:col-span-2" type="search" name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="Invoice, customer or phone" aria-label="Search jobs">
        <select class="input" name="status" aria-label="Status">
            <option value="">Any status</option>
            <?php foreach (job_statuses() as $s): ?><option value="<?= e($s) ?>" <?= ($f['status'] ?? '') === $s ? 'selected' : '' ?>><?= e(job_label($s)) ?></option><?php endforeach; ?>
        </select>
        <?php if ($admin): ?>
        <select class="input" name="payment_status" aria-label="Payment">
            <option value="">Any payment</option>
            <?php foreach (PAYMENT_STATUSES as $s): ?><option value="<?= $s ?>" <?= ($f['payment_status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
        </select>
        <?php endif; ?>
        <input class="input" type="date" name="from" value="<?= e($f['from'] ?? '') ?>" aria-label="From date">
        <input class="input" type="date" name="to" value="<?= e($f['to'] ?? '') ?>" aria-label="To date">
        <div class="flex gap-2 sm:col-span-2 lg:col-span-6">
            <button class="btn-primary btn-sm" type="submit">Apply filters</button>
            <a class="btn-ghost btn-sm" href="<?= url('/jobs') ?>">Clear</a>
        </div>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Invoice</th><th>Customer</th><th>Service</th><th>Date</th><th>Status</th><?php if ($admin): ?><th>Payment</th><?php endif; ?><th class="text-right">Total</th><?php if ($admin): ?><th class="text-right">Balance</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($jobs as $j): ?>
                <tr>
                    <td><a class="font-medium text-link hover:underline" href="<?= url('/jobs/' . $j['id']) ?>"><?= e($j['invoice_number'] ?: 'Draft #' . $j['id']) ?></a></td>
                    <td><?= e($j['customer_name'] ?? '—') ?><?php if ($admin): ?><div class="text-xs text-muted"><?= e($j['phone_number'] ?? '') ?></div><?php endif; ?></td>
                    <td><?= e($j['service_name'] ?? '—') ?></td>
                    <td><?= e(fdate($j['job_date'])) ?></td>
                    <td><?= status_menu($j) ?></td>
                    <?php if ($admin): ?><td><?= status_badge($j['payment_status']) ?></td><?php endif; ?>
                    <td class="num text-right"><?= $j['needs_pricing'] && !$j['is_draft'] ? '<span class="badge badge-pending">Awaiting price</span>' : money($j['total']) ?></td>
                    <?php if ($admin): ?><td class="num text-right"><?= $j['needs_pricing'] ? '—' : money(max(0, $j['total'] - $j['amount_paid'])) ?></td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$jobs): ?><tr><td colspan="8" class="py-12 text-center text-muted">No jobs match these filters. <a class="text-link underline" href="<?= url('/jobs') ?>">Clear filters</a></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php \App\Core\View::partial('partials/pagination', ['pager' => $pager]); ?>
</div>
