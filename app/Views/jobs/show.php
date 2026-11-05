<?php
$j = $job;
$admin = is_admin();
$isDraft = (bool) $j['is_draft'];
$awaiting = !$isDraft && (bool) $j['needs_pricing'];
$perKg = in_array($j['pricing_model'] ?? '', ['per_kg', 'per_hour'], true);   // priced by weight or time, so no per-item prices
$perHour = ($j['pricing_model'] ?? '') === 'per_hour';
$dur = (int) ($j['duration_minutes'] ?? 0);
$durText = $dur ? (intdiv($dur, 60) ? intdiv($dur, 60) . ' h ' : '') . ($dur % 60 ? $dur % 60 . ' min' : '') : '';
$steps = $j['steps'];                                   // this service's own process, e.g. pending > ironing > ready > delivered
$pos = array_search($j['status'], $steps, true);
$next = ($pos !== false && $pos < count($steps) - 1) ? $steps[$pos + 1] : null;
$overdue = !$isDraft && $j['due_date'] && !in_array($j['status'], ['ready', 'delivered', 'cancelled'], true) && $j['due_date'] < date('Y-m-d');
$stepLabel = fn($s) => job_label($s);
$canEdit = $admin || $isDraft;                          // staff can only edit drafts; submitted jobs are locked
?>
<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div>
        <a href="<?= url('/jobs') ?>" class="text-sm text-link hover:underline">All jobs</a>
        <h1 class="flex flex-wrap items-center gap-3 font-display text-2xl font-bold">
            <?= e($j['invoice_number'] ?: 'Draft #' . $j['id']) ?>
            <?= $isDraft ? status_badge('draft') : status_badge($j['status']) ?>
            <?php if ($admin && !$isDraft): ?><?= status_badge($j['payment_status']) ?><?php endif; ?>
            <?php if ($awaiting): ?><span class="badge badge-pending">Awaiting price</span><?php endif; ?>
        </h1>
    </div>
    <div class="flex flex-wrap gap-2">
        <?php if ($isDraft): ?>
            <form method="post" action="<?= url('/jobs/' . $j['id'] . '/finalize') ?>"><?= csrf_field() ?><button class="btn-accent" type="submit"><?= $admin ? 'Create invoice' : 'Submit job' ?></button></form>
        <?php elseif (!$awaiting): ?>
            <a class="btn-accent" href="<?= url('/invoices/' . $j['id']) ?>" target="_blank" rel="noopener">View invoice</a>
        <?php endif; ?>
        <?php if ($canEdit): ?><a class="btn-ghost" href="<?= url('/jobs/' . $j['id'] . '/edit') ?>">Edit</a><?php endif; ?>
        <?php if ($admin): ?>
            <form method="post" action="<?= url('/jobs/' . $j['id'] . '/delete') ?>" data-confirm="Delete this job? It will disappear from lists and reports.">
                <?= csrf_field() ?><button class="btn-danger" type="submit">Delete</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($awaiting && $admin): ?>
    <div class="alert-warning mb-6 flex flex-wrap items-center justify-between gap-3">
        <div><strong>This job has no price yet.</strong> <?= e($j['created_by_name'] ?? 'Staff') ?> submitted it without one. Fill the prices from your service price list, or edit the job to type them in.</div>
        <div class="flex gap-2">
            <form method="post" action="<?= url('/jobs/' . $j['id'] . '/price') ?>"><?= csrf_field() ?><button class="btn-primary btn-sm" type="submit">Price from price list</button></form>
            <a class="btn-ghost btn-sm" href="<?= url('/jobs/' . $j['id'] . '/edit') ?>">Set prices manually</a>
        </div>
    </div>
<?php elseif ($awaiting): ?>
    <div class="alert-info mb-6">The admin has been told and will set the price. You can still update the status as the job moves along.</div>
<?php elseif ($isDraft && !$admin): ?>
    <div class="alert-info mb-6">This is a draft. When you submit it you won't be able to edit it, only update its status, and the admin will set the price.</div>
<?php endif; ?>

<?php if (!$isDraft && $j['status'] === 'cancelled'): ?>
    <div class="alert-error mb-6">This job was cancelled.</div>
<?php elseif (!$isDraft): ?>
<section class="card card-pad mb-6">
    <ol class="flex items-center" aria-label="Job progress">
        <?php foreach ($steps as $idx => $k): $done = $pos !== false && $idx <= $pos; $isLast = $idx === count($steps) - 1; ?>
            <li class="flex <?= $isLast ? '' : 'flex-1' ?> items-center">
                <span class="flex flex-col items-center gap-1">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs font-bold <?= $done ? 'border-sea bg-sea text-white' : 'border-line text-muted' ?>"><?= $done ? '✓' : $idx + 1 ?></span>
                    <span class="text-center text-xs font-medium <?= $done ? 'text-fg' : 'text-muted' ?>"><?= e($stepLabel($k)) ?></span>
                </span>
                <?php if (!$isLast): ?><span class="mx-2 mb-5 h-0.5 flex-1 <?= $pos !== false && $idx < $pos ? 'bg-sea' : 'bg-line' ?>"></span><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4 text-sm">
        <span class="text-muted">
            <?= $overdue ? '<span class="font-semibold text-red-700 dark:text-red-300">Overdue.</span> It was due ' . e(fdate($j['due_date'])) . '.' : ($j['due_date'] ? 'Due ' . e(fdate($j['due_date'], 'D j M')) . '.' : 'No due date set.') ?>
            <?php if ($admin && $j['status'] === 'delivered' && $j['balance'] > 0 && !$awaiting): ?> <span class="font-semibold text-red-700 dark:text-red-300">Delivered but <?= money($j['balance']) ?> is unpaid.</span><?php endif; ?>
        </span>
        <div class="flex flex-wrap items-center gap-2">
            <form method="post" action="<?= url('/jobs/' . $j['id'] . '/status') ?>" class="flex items-center gap-2">
                <?= csrf_field() ?>
                <label class="sr-only" for="status">Job status</label>
                <select class="input w-auto py-1.5" id="status" name="status">
                    <?php foreach ($steps as $k): ?><option value="<?= $k ?>" <?= $j['status'] === $k ? 'selected' : '' ?>><?= e($stepLabel($k)) ?></option><?php endforeach; ?>
                    <?php if ($admin): ?><option value="cancelled">Cancelled</option><?php endif; ?>
                </select>
                <button class="btn-ghost btn-sm" type="submit">Update</button>
            </form>
            <?php if ($next): ?>
                <form method="post" action="<?= url('/jobs/' . $j['id'] . '/status') ?>"><?= csrf_field() ?><input type="hidden" name="status" value="<?= $next ?>">
                    <button class="btn-accent" type="submit">Mark as <?= e(strtolower($stepLabel($next))) ?></button></form>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="grid gap-6 <?= $admin ? 'lg:grid-cols-3' : '' ?>">
    <div class="space-y-6 <?= $admin ? 'lg:col-span-2' : '' ?>">
        <section class="card">
            <div class="grid gap-4 p-5 text-sm sm:grid-cols-3">
                <div><div class="text-muted">Customer</div>
                    <?php if ($j['customer_id']): ?>
                        <?php if ($admin): ?><a class="font-medium text-link hover:underline" href="<?= url('/customers/' . $j['customer_id']) ?>"><?= e($j['customer_name']) ?></a>
                        <?php else: ?><span class="font-medium"><?= e($j['customer_name']) ?></span><?php endif; ?>
                        <div class="text-muted"><?= e($j['phone_number'] ?? '') ?></div>
                    <?php else: ?>—<?php endif; ?></div>
                <div><div class="text-muted">Service</div><div class="font-medium"><?= e($j['service_name'] ?? '—') ?></div>
                    <div class="text-xs text-muted"><?= e(implode(' → ', array_map($stepLabel, $steps))) ?></div></div>
                <div><div class="text-muted">Dates</div><div class="font-medium">In: <?= e(fdate($j['job_date'])) ?></div><div class="text-muted">Ready by: <?= e(fdate($j['due_date'])) ?></div></div>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Item</th><th>Colour</th><th>Length</th><th class="text-right">Qty</th><?php if (!$perKg): ?><th class="text-right">Price</th><th class="text-right">Amount</th><?php endif; ?></tr></thead>
                    <tbody>
                    <?php foreach ($j['items'] as $it): ?>
                        <tr>
                            <td><?= e($it['type_label']) ?></td>
                            <td><?= e(option_label($it['color'])) ?></td>
                            <td><?= e(option_label($it['length'])) ?></td>
                            <td class="num text-right"><?= (int) $it['quantity'] ?></td>
                            <?php if (!$perKg): ?>
                                <td class="num text-right"><?= $awaiting || (float) $it['price_per_unit'] <= 0 && $j['needs_pricing'] ? '<span class="text-muted">—</span>' : money($it['price_per_unit']) ?></td>
                                <td class="num text-right"><?= $awaiting || (float) $it['price_per_unit'] <= 0 && $j['needs_pricing'] ? '<span class="text-muted">—</span>' : money($it['amount']) ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <dl class="num ml-auto max-w-xs space-y-1 p-5 text-sm">
                <?php if ($awaiting || ($isDraft && $j['needs_pricing'])): ?>
                    <div class="flex justify-between font-semibold"><dt>Total</dt><dd class="text-muted">Awaiting price</dd></div>
                <?php else: ?>
                    <?php if ($perHour): ?>
                        <div class="flex justify-between"><dt class="text-muted">Machine time</dt><dd><?= e(trim($durText)) ?> × <?= money($j['price_per_hour']) ?>/hr</dd></div>
                    <?php elseif ($perKg): ?>
                        <div class="flex justify-between"><dt class="text-muted">Weight</dt><dd><?= e(rtrim(rtrim((string) $j['total_kg'], '0'), '.')) ?> kg × <?= money($j['price_per_kg']) ?></dd></div>
                    <?php endif; ?>
                    <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd><?= money($j['subtotal']) ?></dd></div>
                    <?php if ((float) $j['discount_amount'] > 0): ?>
                        <div class="flex justify-between"><dt class="text-muted">Discount<?= $j['discount_type'] === 'percent' ? ' (' . (float) $j['discount_value'] . '%)' : '' ?></dt><dd>−<?= money($j['discount_amount']) ?></dd></div>
                    <?php endif; ?>
                    <div class="flex justify-between border-t border-line pt-2 text-base font-bold"><dt>Total</dt><dd><?= money($j['total']) ?></dd></div>
                    <?php if ($admin): ?>
                        <div class="flex justify-between"><dt class="text-muted">Paid</dt><dd><?= money($j['amount_paid']) ?></dd></div>
                        <div class="flex justify-between font-semibold"><dt>Balance</dt><dd><?= money($j['balance']) ?></dd></div>
                    <?php endif; ?>
                <?php endif; ?>
            </dl>
            <?php if ($j['notes']): ?><div class="border-t border-line p-5 text-sm"><div class="text-muted">Notes</div><p class="whitespace-pre-line"><?= e($j['notes']) ?></p></div><?php endif; ?>
        </section>

        <?php if ($admin): ?>
        <section class="card">
            <h2 class="px-5 py-4 font-display text-lg font-bold">Payments</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Date</th><th>Method</th><th>Reference</th><th>Received by</th><th class="text-right">Amount</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($j['payments'] as $p): ?>
                        <tr>
                            <td><?= e(fdate($p['paid_at'], 'd M Y, H:i')) ?></td>
                            <td><?= e(ucfirst($p['method'])) ?></td>
                            <td><?= e($p['reference'] ?: '—') ?></td>
                            <td><?= e($p['received_by_name'] ?: '—') ?></td>
                            <td class="num text-right"><?= money($p['amount']) ?></td>
                            <td class="text-right"><form method="post" action="<?= url('/payments/' . $p['id'] . '/delete') ?>" data-confirm="Remove this payment?"><?= csrf_field() ?><button class="btn-danger btn-sm" type="submit">Remove</button></form></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$j['payments']): ?><tr><td colspan="6" class="py-6 text-center text-muted">No payments yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <?php if ($admin): ?>
    <aside class="space-y-6">
        <?php if (!$isDraft && !$awaiting && $j['balance'] > 0 && $j['status'] !== 'cancelled'): ?>
        <section class="card card-pad">
            <h2 class="font-display text-lg font-bold">Record a payment</h2>
            <form method="post" action="<?= url('/jobs/' . $j['id'] . '/payments') ?>" class="mt-3 space-y-3">
                <?= csrf_field() ?>
                <div>
                    <label class="label" for="amount">Amount</label>
                    <input class="input" type="number" step="0.01" min="0.01" max="<?= e($j['balance']) ?>" id="amount" name="amount" value="<?= e($j['balance']) ?>" required>
                    <p class="hint">Outstanding: <?= money($j['balance']) ?></p>
                </div>
                <div>
                    <label class="label" for="method">Method</label>
                    <select class="input" id="method" name="method"><?php foreach (PAYMENT_METHODS as $m): ?><option value="<?= $m ?>"><?= ucfirst($m) ?></option><?php endforeach; ?></select>
                </div>
                <div>
                    <label class="label" for="reference">Reference (optional)</label>
                    <input class="input" id="reference" name="reference" placeholder="Transfer ref or POS slip no.">
                </div>
                <button class="btn-accent w-full" type="submit">Record payment</button>
            </form>
        </section>
        <?php endif; ?>
        <section class="card card-pad text-sm text-muted">
            Created by <?= e($j['created_by_name'] ?? 'unknown') ?> on <?= e(fdate($j['created_at'], 'd M Y, H:i')) ?>
        </section>
    </aside>
    <?php endif; ?>
</div>
