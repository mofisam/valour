<?php
\App\Core\View::script('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js');
\App\Core\View::script('charts.js');
\App\Core\View::script('status-menu.js');
$admin = is_admin();
$i = $insights;
$monthStart = date('Y-m-01');
$periodLabel = date('F Y') . ' · 1 ' . date('M') . ' to ' . date('j M');   // the month always starts on day 1

/** "▲ 12% vs same days last month" chip. $goodWhenUp=false flips colours (for expenses). */
$delta = function (float $now, float $prev, bool $goodWhenUp = true): string {
    if ($prev <= 0) {
        return $now > 0 ? '<span class="text-xs text-muted">No figures for last month</span>' : '<span class="text-xs text-muted">&nbsp;</span>';
    }
    $pct = ($now - $prev) / $prev * 100;
    $up = $pct >= 0;
    $cls = ($up === $goodWhenUp) ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300';
    return '<span class="text-xs font-semibold ' . $cls . '">' . ($up ? '▲' : '▼') . ' ' . number_format(abs($pct), 0) . '%</span> <span class="text-xs text-muted">vs same days last month</span>';
};
$pricingCount = $admin ? count($attention['pricing']) : 0;
$attentionCount = count($attention['overdue']) + count($attention['waiting']) + $pricingCount;
?>
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="font-display text-2xl font-bold"><?= date('G') < 12 ? 'Good morning' : (date('G') < 17 ? 'Good afternoon' : 'Good evening') ?>, <?= e(explode(' ', auth()['name'])[0]) ?></h1>
        <p class="text-sm text-muted">
            <?= (int) $stats['jobs_today'] ?> job<?= $stats['jobs_today'] == 1 ? '' : 's' ?> today · <?= (int) $stats['ready_jobs'] ?> ready for pickup<?= $attentionCount ? ' · <span class="font-semibold text-red-700 dark:text-red-300">' . $attentionCount . ' need attention</span>' : '' ?>
        </p>
    </div>
    <a href="<?= url('/jobs/create') ?>" class="btn-primary"><?= icon('plus', 'h-4 w-4') ?> Create a job</a>
</div>

<?php if ($admin && $pricingCount): ?>
    <a href="<?= url('/jobs?view=pricing') ?>" class="alert-warning mb-4 flex items-center justify-between gap-3 hover:opacity-90">
        <span class="flex items-center gap-2"><?= icon('tag', 'h-5 w-5 shrink-0') ?><span><strong><?= $pricingCount ?><?= $pricingCount >= 6 ? '+' : '' ?> job<?= $pricingCount === 1 ? '' : 's' ?> waiting for a price.</strong> Staff submitted them without one.</span></span>
        <span class="font-semibold">Set prices</span>
    </a>
<?php endif; ?>

<?php if ($admin && $dueReminders): ?>
    <a href="<?= url('/reminders') ?>" class="alert-warning mb-6 flex items-center justify-between gap-3 hover:opacity-90">
        <span class="flex items-center gap-2"><?= icon('clock', 'h-5 w-5 shrink-0') ?><span><strong><?= count($dueReminders) ?> reminder<?= count($dueReminders) === 1 ? '' : 's' ?> due:</strong> <?= e($dueReminders[0]['title']) ?><?= count($dueReminders) > 1 ? ' and ' . (count($dueReminders) - 1) . ' more' : '' ?></span></span>
        <span class="font-semibold">Open</span>
    </a>
<?php endif; ?>

<?php if ($admin): ?>
<!-- Accounting figures: calendar month to date (from day 1) -->
<div class="mb-3 flex items-center gap-2 text-sm text-muted"><?= icon('calendar', 'h-4 w-4') ?> <?= e($periodLabel) ?> <span class="hidden sm:inline">· month to date</span></div>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <a href="<?= url('/reports?from=' . $monthStart . '&to=' . date('Y-m-d')) ?>" class="card card-pad block hover:border-sea">
        <div class="flex items-start justify-between"><div class="text-sm text-muted">Revenue</div><?= icon_chip('trending-up', 'sea') ?></div>
        <div class="num mt-1 font-display text-2xl font-bold"><?= money($i['rev_mtd']) ?></div>
        <div class="mt-1"><?= $delta($i['rev_mtd'], $i['rev_prev']) ?></div>
    </a>
    <div class="card card-pad">
        <div class="flex items-start justify-between"><div class="text-sm text-muted">Net profit for the month</div><?= icon_chip('percent', $i['net_mtd'] < 0 ? 'red' : 'green') ?></div>
        <div class="num mt-1 font-display text-2xl font-bold <?= $i['net_mtd'] < 0 ? 'text-red-700 dark:text-red-300' : '' ?>"><?= money($i['net_mtd']) ?></div>
        <div class="mt-1"><?= $delta($i['net_mtd'], $i['net_prev']) ?></div>
    </div>
    <a href="<?= url('/receivables') ?>" class="card card-pad block hover:border-red-400">
        <div class="flex items-start justify-between"><div class="text-sm text-muted">Receivables</div><?= icon_chip('file-text', 'red') ?></div>
        <div class="num mt-1 font-display text-2xl font-bold text-red-700 dark:text-red-300"><?= money($owed['total']) ?></div>
        <div class="mt-1 text-xs text-muted">from <?= (int) $owed['customers'] ?> customer<?= $owed['customers'] === 1 ? '' : 's' ?> · view ageing</div>
    </a>
    <a href="<?= url('/cash?from=' . $monthStart . '&to=' . date('Y-m-d')) ?>" class="card card-pad block hover:border-sea">
        <div class="flex items-start justify-between"><div class="text-sm text-muted">Bank / Cash received</div><?= icon_chip('wallet', 'brass') ?></div>
        <div class="num mt-1 font-display text-2xl font-bold"><?= money($i['cash_mtd']) ?></div>
        <div class="mt-1"><?= $delta($i['cash_mtd'], $i['cash_prev']) ?></div>
    </a>
</div>
<?php endif; ?>

<!-- Operations -->
<div class="mt-4 grid grid-cols-2 gap-4 <?= $admin ? 'xl:grid-cols-4' : 'xl:grid-cols-3' ?>">
    <div class="card card-pad"><div class="flex items-start justify-between"><div class="text-sm text-muted">Jobs today</div><?= icon_chip('clipboard', 'ink') ?></div><div class="num mt-1 font-display text-2xl font-bold"><?= (int) $stats['jobs_today'] ?></div></div>
    <a href="<?= url('/jobs?status=ready') ?>" class="card card-pad block hover:border-sea"><div class="flex items-start justify-between"><div class="text-sm text-muted">Ready for pickup</div><?= icon_chip('package', 'sea') ?></div><div class="num mt-1 font-display text-2xl font-bold"><?= (int) $stats['ready_jobs'] ?></div></a>
    <?php if ($admin): ?>
        <a href="<?= url('/expenses') ?>" class="card card-pad block hover:border-sea"><div class="flex items-start justify-between"><div class="text-sm text-muted">Expenses this month</div><?= icon_chip('credit-card', 'red') ?></div><div class="num mt-1 font-display text-2xl font-bold"><?= money($i['exp_mtd']) ?></div><div class="mt-1"><?= $delta($i['exp_mtd'], $i['exp_prev'], false) ?></div></a>
        <div class="card card-pad"><div class="flex items-start justify-between"><div class="text-sm text-muted">Average revenue per job</div><?= icon_chip('bar-chart', 'brass') ?></div><div class="num mt-1 font-display text-2xl font-bold"><?= money($i['avg_job']) ?></div><div class="mt-1 text-xs text-muted"><?= (int) $i['jobs_month'] ?> jobs · <?= (int) $i['new_customers'] ?> new customers</div></div>
    <?php else: ?>
        <a href="<?= url('/jobs?view=drafts') ?>" class="card card-pad block hover:border-sea"><div class="flex items-start justify-between"><div class="text-sm text-muted">Drafts to finish</div><?= icon_chip('file-text', 'ink') ?></div><div class="num mt-1 font-display text-2xl font-bold"><?= (int) $attention['drafts'] ?></div></a>
    <?php endif; ?>
</div>

<?php if ($admin): ?>
<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <section class="card">
        <div class="flex items-center justify-between px-5 py-4"><h2 class="panel-title flex items-center gap-2"><?= icon('file-text', 'h-5 w-5 text-muted') ?> Top receivables</h2><a class="text-sm font-medium text-link hover:underline" href="<?= url('/receivables') ?>">See all</a></div>
        <?php if (!$debtors): ?><p class="px-5 pb-5 text-sm text-muted">No outstanding receivables.</p>
        <?php else: ?>
        <ul class="divide-y divide-line border-t border-line">
            <?php foreach ($debtors as $d): ?>
                <li><a class="flex items-center justify-between gap-3 px-5 py-3 text-sm hover:bg-canvas" href="<?= url('/customers/' . $d['id']) ?>">
                    <span class="min-w-0"><span class="block truncate font-medium"><?= e($d['full_name']) ?></span><span class="text-xs text-muted"><?= e($d['phone_number'] ?: 'No phone') ?></span></span>
                    <span class="num shrink-0 font-semibold text-red-700 dark:text-red-300"><?= money($d['outstanding']) ?></span>
                </a></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>

    <section class="card card-pad">
        <h2 class="panel-title flex items-center gap-2"><?= icon('layers', 'h-5 w-5 text-muted') ?> Jobs by status</h2>
        <p class="text-sm text-muted"><?= e(date('F Y')) ?> · still to finish</p>
        <?php if (!array_sum(array_map('intval', $i['status_counts']))): ?>
            <p class="mt-4 text-sm text-muted">Nothing is waiting to be finished this month.</p>
        <?php else: ?>
            <div class="mx-auto mt-3 h-44 max-w-[11rem]"><canvas id="statusChart" data-counts='<?= e(json_encode($i['status_counts'])) ?>' data-meta='<?= e(json_encode((object) array_map(fn($st) => ['label' => $st['label'], 'color' => STEP_HEX[$st['color']] ?? '#7A8B99'], job_steps()))) ?>'></canvas></div>
            <ul class="mt-4 grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-muted">
                <?php foreach ($i['status_counts'] as $st => $n): ?><li><a class="flex justify-between hover:text-link" href="<?= url('/jobs?status=' . $st) ?>"><span><?= e(job_label($st)) ?></span><span class="num font-semibold text-fg"><?= (int) $n ?></span></a></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="card card-pad">
        <h2 class="panel-title flex items-center gap-2"><?= icon('tag', 'h-5 w-5 text-muted') ?> Top services this month</h2>
        <?php if (!$i['top_services']): ?><p class="mt-3 text-sm text-muted">No jobs billed this month yet.</p>
        <?php else: $max = max(array_map(fn($x) => (float) $x['billed'], $i['top_services'])) ?: 1; ?>
            <ul class="mt-3 space-y-3 text-sm">
                <?php foreach ($i['top_services'] as $sv): ?>
                    <li><div class="flex justify-between"><span class="font-medium"><?= e($sv['name']) ?></span><span class="num text-muted"><?= money($sv['billed']) ?></span></div>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-canvas2"><div class="h-full rounded-full bg-brass" style="width:<?= round($sv['billed'] / $max * 100) ?>%"></div></div>
                        <div class="mt-0.5 text-xs text-muted"><?= (int) $sv['jobs'] ?> job<?= $sv['jobs'] == 1 ? '' : 's' ?></div></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <a class="btn-ghost btn-sm mt-5" href="<?= url('/reports') ?>"><?= icon('bar-chart', 'h-4 w-4') ?> Full reports and charts</a>
    </section>
</div>
<?php endif; ?>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <section class="card lg:col-span-2">
        <h2 class="panel-title flex items-center gap-2 px-5 pt-4"><?= icon('alert', 'h-5 w-5 text-muted') ?> Needs attention</h2>
        <?php if (!$attentionCount): ?>
            <p class="p-5 text-sm text-muted">Nothing is late or waiting too long. Everything is on schedule.</p>
        <?php else: ?>
        <ul class="divide-y divide-line">
            <?php if ($admin): foreach ($attention['pricing'] as $j): ?>
                <li><a class="flex items-center justify-between gap-3 px-5 py-3 text-sm hover:bg-canvas" href="<?= url('/jobs/' . $j['id']) ?>">
                    <span><span class="font-medium"><?= e($j['invoice_number']) ?></span> · <?= e($j['customer_name'] ?? '—') ?><span class="block text-xs text-muted">Submitted without a price</span></span>
                    <span class="badge badge-pending">Needs price</span></a></li>
            <?php endforeach; endif; ?>
            <?php foreach ($attention['overdue'] as $j): ?>
                <li><a class="flex items-center justify-between gap-3 px-5 py-3 text-sm hover:bg-canvas" href="<?= url('/jobs/' . $j['id']) ?>">
                    <span><span class="font-medium"><?= e($j['invoice_number']) ?></span> · <?= e($j['customer_name'] ?? '—') ?><span class="block text-xs text-muted"><?= status_badge($j['status']) ?> was due <?= e(fdate($j['due_date'], 'D j M')) ?></span></span>
                    <span class="badge badge-cancelled">Overdue</span></a></li>
            <?php endforeach; ?>
            <?php foreach ($attention['waiting'] as $j): ?>
                <li><a class="flex items-center justify-between gap-3 px-5 py-3 text-sm hover:bg-canvas" href="<?= url('/jobs/' . $j['id']) ?>">
                    <span><span class="font-medium"><?= e($j['invoice_number']) ?></span> · <?= e($j['customer_name'] ?? '—') ?><span class="block text-xs text-muted">Ready since <?= e(fdate($j['updated_at'], 'D j M')) ?>, not collected</span></span>
                    <span class="badge badge-ready">Waiting</span></a></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>

    <div class="space-y-6">
        <?php if ($admin): ?>
        <section class="card card-pad">
            <div class="flex items-center justify-between"><h2 class="panel-title flex items-center gap-2"><?= icon('clock', 'h-5 w-5 text-muted') ?> Reminders</h2><a class="text-sm font-medium text-link hover:underline" href="<?= url('/reminders') ?>">Manage</a></div>
            <?php $show = array_merge($dueReminders, $upcomingReminders); if (!$show): ?>
                <p class="mt-3 text-sm text-muted">No reminders set. <a class="text-link underline" href="<?= url('/reminders') ?>">Add one</a> so nothing slips.</p>
            <?php else: ?>
                <ul class="mt-3 space-y-3 text-sm">
                    <?php foreach (array_slice($show, 0, 4) as $r): $isDue = strtotime($r['remind_at']) <= time(); ?>
                        <li class="flex items-start gap-2"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full <?= $isDue ? 'bg-red-600' : 'bg-sea' ?>"></span>
                            <span><span class="font-medium"><?= e($r['title']) ?></span><span class="block text-xs text-muted"><?= $isDue ? 'Due ' : '' ?><?= e(fdate($r['remind_at'], 'D j M, g:i A')) ?></span></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="card card-pad">
            <h2 class="panel-title flex items-center gap-2"><?= icon('users', 'h-5 w-5 text-muted') ?> Birthdays this month</h2>
            <?php if (!$birthdays): ?>
                <p class="mt-3 text-sm text-muted">No customer birthdays this month.</p>
            <?php else: ?>
                <ul class="mt-3 divide-y divide-line text-sm">
                    <?php foreach (array_slice($birthdays, 0, 6) as $b): ?>
                        <li class="flex items-center justify-between py-2">
                            <a class="font-medium hover:text-link" href="<?= url('/customers/' . $b['id']) ?>"><?= e($b['full_name']) ?></a>
                            <span class="text-muted"><?= e(fdate($b['birthday'], 'j M')) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <section class="card <?= $admin ? 'xl:col-span-2' : 'xl:col-span-3' ?>">
        <div class="flex items-center justify-between px-5 py-4">
            <h2 class="panel-title flex items-center gap-2"><?= icon('clipboard', 'h-5 w-5 text-muted') ?> Latest jobs</h2>
            <a class="text-sm font-medium text-link hover:underline" href="<?= url('/jobs') ?>">See all jobs</a>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Invoice</th><th>Customer</th><th>Status</th><?php if ($admin): ?><th>Payment</th><?php endif; ?><th class="text-right">Total</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $j): ?>
                    <tr>
                        <td><a class="font-medium text-link hover:underline" href="<?= url('/jobs/' . $j['id']) ?>"><?= e($j['invoice_number'] ?: 'Draft #' . $j['id']) ?></a><div class="text-xs text-muted"><?= e(fdate($j['job_date'])) ?></div></td>
                        <td><?= e($j['customer_name'] ?? '—') ?></td>
                        <td><?= status_menu($j) ?></td>
                        <?php if ($admin): ?><td><?= status_badge($j['payment_status']) ?></td><?php endif; ?>
                        <td class="num text-right"><?= $j['needs_pricing'] && !$j['is_draft'] ? '<span class="badge badge-pending">Awaiting price</span>' : money($j['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recent): ?><tr><td colspan="5" class="py-10 text-center text-muted">No jobs yet. Create the first one.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($admin): ?>
    <section class="card card-pad">
        <h2 class="panel-title flex items-center gap-2"><?= icon('activity', 'h-5 w-5 text-muted') ?> Recent activity</h2>
        <ul class="mt-3 space-y-3 text-sm">
            <?php foreach ($i['activity'] as $a): ?>
                <li><span class="font-medium"><?= e($a['user_name'] ?? 'System') ?></span> <span class="text-muted"><?= e(str_replace(['.', '_'], ' ', $a['action'])) ?></span>
                    <?php if ($a['details']): ?><span class="block truncate text-xs text-muted"><?= e($a['details']) ?></span><?php endif; ?>
                    <span class="block text-xs text-muted"><?= e(fdate($a['created_at'], 'j M, g:i A')) ?></span></li>
            <?php endforeach; ?>
            <?php if (!$i['activity']): ?><li class="text-muted">Nothing yet.</li><?php endif; ?>
        </ul>
        <a class="mt-4 inline-block text-sm font-medium text-link hover:underline" href="<?= url('/activity') ?>">Open activity log</a>
    </section>
    <?php endif; ?>
</div>
