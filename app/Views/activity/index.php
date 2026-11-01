<?php
$f = $filters;
$labels = [
    'login' => 'Signed in', 'logout' => 'Signed out',
    'job.created' => 'Created a job', 'job.updated' => 'Edited a job', 'job.status' => 'Changed job status', 'job.finalized' => 'Created an invoice', 'job.deleted' => 'Deleted a job',
    'payment.added' => 'Recorded a payment', 'payment.deleted' => 'Removed a payment',
    'customer.created' => 'Added a customer', 'customer.updated' => 'Edited a customer', 'customer.deleted' => 'Removed a customer',
    'expense.created' => 'Added an expense', 'expense.updated' => 'Edited an expense', 'expense.deleted' => 'Deleted an expense',
    'service.created' => 'Added a service', 'service.updated' => 'Edited a service',
    'clothing_type.created' => 'Added a clothing type', 'clothing_type.updated' => 'Edited a clothing type',
    'expense_category.created' => 'Added an expense category', 'expense_category.updated' => 'Edited an expense category', 'expense_category.deleted' => 'Deleted an expense category',
    'user.created' => 'Created a staff account', 'user.updated' => 'Edited a staff account',
    'job.priced' => 'Priced a job', 'prices.updated' => 'Updated the price list', 'profile.updated' => 'Updated own profile',
    'reminder.created' => 'Set a reminder', 'reminder.done' => 'Completed a reminder', 'reminder.deleted' => 'Deleted a reminder',
    'password.changed' => 'Changed password', 'settings.updated' => 'Changed business settings',
];
$link = function (array $l): ?string {
    return match ($l['entity_type']) {
        'job' => $l['entity_id'] ? '/jobs/' . $l['entity_id'] : null,
        'customer' => $l['entity_id'] ? '/customers/' . $l['entity_id'] : null,
        'reminder' => '/reminders', default => null,
    };
};
?>
<div class="mb-5">
    <h1 class="font-display text-2xl font-bold">Activity log</h1>
    <p class="text-sm text-muted">A record of what staff and admins did in the system, newest first.</p>
</div>

<div class="card">
    <form method="get" class="grid gap-3 border-b border-line p-4 sm:grid-cols-2 lg:grid-cols-5">
        <select class="input" name="user_id" aria-label="Person">
            <option value="">Everyone</option>
            <?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>" <?= (string) ($f['user_id'] ?? '') === (string) $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option><?php endforeach; ?>
        </select>
        <select class="input" name="entity" aria-label="Area">
            <option value="">All areas</option>
            <?php foreach ($entities as $en): ?><option value="<?= e($en) ?>" <?= ($f['entity'] ?? '') === $en ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $en))) ?></option><?php endforeach; ?>
        </select>
        <input class="input" type="date" name="from" value="<?= e($f['from'] ?? '') ?>" aria-label="From date">
        <input class="input" type="date" name="to" value="<?= e($f['to'] ?? '') ?>" aria-label="To date">
        <div class="flex gap-2"><button class="btn-primary btn-sm" type="submit">Filter</button><a class="btn-ghost btn-sm" href="<?= url('/activity') ?>">Clear</a></div>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>When</th><th>Who</th><th>What</th><th>Details</th><th>IP address</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $l): $href = $link($l); ?>
                <tr>
                    <td class="whitespace-nowrap"><?= e(fdate($l['created_at'], 'd M Y, g:i A')) ?></td>
                    <td><?= e($l['user_name'] ?? 'System') ?></td>
                    <td class="font-medium"><?= $href ? '<a class="text-link hover:underline" href="' . url($href) . '">' . e($labels[$l['action']] ?? $l['action']) . '</a>' : e($labels[$l['action']] ?? ucfirst(str_replace(['.', '_'], ' ', $l['action']))) ?></td>
                    <td class="max-w-md text-muted"><?= ($l['details'] ?? '') !== '' ? e($l['details']) : ($l['entity_type'] ? e(ucfirst(str_replace('_', ' ', $l['entity_type']))) . ($l['entity_id'] ? ' #' . (int) $l['entity_id'] : '') : '—') ?></td>
                    <td class="text-muted"><?= e($l['ip_address'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?><tr><td colspan="5" class="py-10 text-center text-muted">No activity matches these filters.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php \App\Core\View::partial('partials/pagination', ['pager' => $pager]); ?>
</div>
