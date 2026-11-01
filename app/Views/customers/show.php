<?php $c = $customer; $owes = (float) $c['outstanding'] > 0; ?>
<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div>
        <a href="<?= url('/customers') ?>" class="text-sm text-link hover:underline">All customers</a>
        <h1 class="font-display text-2xl font-bold"><?= e($c['full_name']) ?></h1>
        <p class="text-sm text-muted">Customer since <?= e(fdate($c['created_at'], 'F Y')) ?></p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a class="btn-accent" href="<?= url('/jobs/create?customer_id=' . $c['id']) ?>">New job</a>
        <?php if (is_admin()): ?>
            <a class="btn-ghost" href="<?= url('/customers/' . $c['id'] . '/edit') ?>">Edit</a>
            <form method="post" action="<?= url('/customers/' . $c['id'] . '/delete') ?>" data-confirm="Remove this customer? Their past jobs and invoices are kept.">
                <?= csrf_field() ?><button class="btn-danger" type="submit">Remove</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Balance banner -->
<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="card card-pad sm:col-span-2 xl:col-span-1 <?= $owes ? 'border-red-300 bg-red-50 dark:border-red-500/40 dark:bg-red-500/10' : '' ?>">
        <div class="text-sm <?= $owes ? 'text-red-800 dark:text-red-200' : 'text-muted' ?>">Receivable</div>
        <div class="num mt-1 font-display text-3xl font-bold <?= $owes ? 'text-red-700 dark:text-red-300' : '' ?>"><?= money($c['outstanding']) ?></div>
        <div class="mt-1 text-xs <?= $owes ? 'text-red-800/80 dark:text-red-200/80' : 'text-muted' ?>"><?= $owes ? 'Open the jobs below with a balance to record a payment.' : 'Fully paid up. No receivable.' ?></div>
    </div>
    <div class="card card-pad"><div class="text-sm text-muted">Total billed</div><div class="num mt-1 font-display text-2xl font-bold"><?= money($c['total_billed']) ?></div></div>
    <div class="card card-pad"><div class="text-sm text-muted">Total paid</div><div class="num mt-1 font-display text-2xl font-bold"><?= money($c['total_paid']) ?></div></div>
    <div class="card card-pad"><div class="text-sm text-muted">Jobs</div><div class="num mt-1 font-display text-2xl font-bold"><?= (int) $c['job_count'] ?></div></div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <section class="card card-pad h-fit space-y-4 text-sm lg:col-span-1">
        <h2 class="panel-title">Contact</h2>
        <div><div class="text-muted">Phone</div><div class="font-medium"><?= e($c['phone_number'] ?: '—') ?></div></div>
        <div><div class="text-muted">Email</div><div class="font-medium"><?= e($c['email'] ?: '—') ?></div></div>
        <div><div class="text-muted">Birthday</div><div class="font-medium"><?= e(fdate($c['birthday'], 'j F')) ?></div></div>
        <div><div class="text-muted">Address</div><div class="whitespace-pre-line font-medium"><?= e($c['address'] ?: '—') ?></div></div>
        <?php if ($c['notes']): ?><div><div class="text-muted">Notes</div><div class="whitespace-pre-line"><?= e($c['notes']) ?></div></div><?php endif; ?>
    </section>

    <section class="card lg:col-span-2">
        <h2 class="panel-title px-5 py-4">Job history</h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Invoice</th><th>Date</th><th>Status</th><th>Payment</th><th class="text-right">Total</th><th class="text-right">Balance</th></tr></thead>
                <tbody>
                <?php foreach ($jobs as $j): $bal = max(0, $j['total'] - $j['amount_paid']); ?>
                    <tr>
                        <td><a class="font-medium text-link hover:underline" href="<?= url('/jobs/' . $j['id']) ?>"><?= e($j['invoice_number'] ?: 'Draft #' . $j['id']) ?></a><div class="text-xs text-muted"><?= e($j['service_name'] ?? '') ?></div></td>
                        <td><?= e(fdate($j['job_date'])) ?></td>
                        <td><?= $j['is_draft'] ? status_badge('draft') : status_badge($j['status']) ?></td>
                        <td><?= status_badge($j['payment_status']) ?></td>
                        <td class="num text-right"><?= money($j['total']) ?></td>
                        <td class="num text-right">
                            <?php if ($bal > 0 && !$j['is_draft'] && $j['status'] !== 'cancelled'): ?>
                                <a class="font-semibold text-red-700 hover:underline dark:text-red-300" href="<?= url('/jobs/' . $j['id']) ?>"><?= money($bal) ?></a>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$jobs): ?><tr><td colspan="6" class="py-10 text-center text-muted">No jobs for this customer yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
