<?php
\App\Core\View::script('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js');
\App\Core\View::script('cash.js');
$s = $sum;
$qs = fn(array $o) => url('/cash?' . http_build_query(array_filter(['from' => $o['from'] ?? $from, 'to' => $o['to'] ?? $to, 'method' => $o['method'] ?? $method], fn($v) => $v !== '')));
$methodTabs = ['' => 'All', 'cash' => 'Cash', 'bank' => 'Bank (transfer, POS, other)', 'transfer' => 'Transfer', 'pos' => 'POS'];
$methodLabel = fn($m) => $m === 'cash' ? 'Cash' : ($m === 'pos' ? 'POS' : ucfirst($m));
?>
<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <a href="<?= url('/') ?>" class="text-sm text-link hover:underline">Dashboard</a>
        <h1 class="flex items-center gap-2 font-display text-2xl font-bold"><?= icon('wallet', 'h-6 w-6 text-sea') ?> Bank &amp; Cash</h1>
        <p class="text-sm text-muted">Money actually received, <?= e(fdate($from, 'j M Y')) ?> to <?= e(fdate($to, 'j M Y')) ?>.</p>
    </div>
    <form method="get" class="flex flex-wrap items-end gap-3">
        <?php if ($method !== ''): ?><input type="hidden" name="method" value="<?= e($method) ?>"><?php endif; ?>
        <div><label class="label" for="from">From</label><input class="input" type="date" id="from" name="from" value="<?= e($from) ?>"></div>
        <div><label class="label" for="to">To</label><input class="input" type="date" id="to" name="to" value="<?= e($to) ?>"></div>
        <button class="btn-primary" type="submit">Show</button>
        <a class="btn-ghost" href="<?= url('/reports/export?from=' . urlencode($from) . '&to=' . urlencode($to)) ?>"><?= icon('download', 'h-4 w-4') ?> CSV</a>
    </form>
</div>

<div class="mb-4 flex flex-wrap gap-2">
    <?php foreach ([
        'This month' => [date('Y-m-01'), date('Y-m-d')],
        'Last month' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('first day of last month'))],
        'This year'  => [date('Y-01-01'), date('Y-m-d')],
    ] as $label => [$a, $b]): ?>
        <a class="tab <?= ($from === $a && $to === $b) ? 'tab-active' : '' ?>" href="<?= $qs(['from' => $a, 'to' => $b]) ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="card card-pad">
        <div class="flex items-start justify-between"><div class="text-sm text-muted">Total received</div><?= icon_chip('wallet', 'sea') ?></div>
        <div class="num mt-1 font-display text-2xl font-bold"><?= money($s['total']) ?></div>
        <div class="mt-1 text-xs text-muted"><?= (int) $s['count'] ?> payment<?= $s['count'] === 1 ? '' : 's' ?><?= $s['count'] ? ' · average ' . money($s['total'] / $s['count']) : '' ?></div>
    </div>
    <div class="card card-pad">
        <div class="flex items-start justify-between"><div class="text-sm text-muted">Cash</div><?= icon_chip('banknote', 'green') ?></div>
        <div class="num mt-1 font-display text-2xl font-bold"><?= money($s['cash']) ?></div>
        <div class="mt-1 text-xs text-muted"><?= $s['total'] > 0 ? round($s['cash'] / $s['total'] * 100) . '% of received' : '—' ?></div>
    </div>
    <div class="card card-pad">
        <div class="flex items-start justify-between"><div class="text-sm text-muted">Bank</div><?= icon_chip('bank', 'brass') ?></div>
        <div class="num mt-1 font-display text-2xl font-bold"><?= money($s['bank']) ?></div>
        <div class="mt-1 text-xs text-muted"><?= $s['total'] > 0 ? round($s['bank'] / $s['total'] * 100) . '% of received' : '—' ?></div>
    </div>
    <div class="card card-pad">
        <div class="text-sm text-muted">Bank breakdown</div>
        <dl class="num mt-2 space-y-1 text-sm">
            <div class="flex justify-between"><dt class="flex items-center gap-1.5 text-muted"><?= icon('bank', 'h-4 w-4') ?> Transfer</dt><dd><?= money($s['transfer']) ?></dd></div>
            <div class="flex justify-between"><dt class="flex items-center gap-1.5 text-muted"><?= icon('credit-card', 'h-4 w-4') ?> POS</dt><dd><?= money($s['pos']) ?></dd></div>
            <div class="flex justify-between"><dt class="flex items-center gap-1.5 text-muted"><?= icon('smartphone', 'h-4 w-4') ?> Other</dt><dd><?= money($s['other']) ?></dd></div>
        </dl>
    </div>
</div>

<!-- Money out (expenses) and the net movement, by where the money sits -->
<div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="card card-pad">
        <div class="flex items-start justify-between"><div class="text-sm text-muted">Spent from cash</div><?= icon_chip('banknote', 'red') ?></div>
        <div class="num mt-1 font-display text-xl font-bold"><?= money($s['spent_cash']) ?></div>
    </div>
    <div class="card card-pad">
        <div class="flex items-start justify-between"><div class="text-sm text-muted">Spent from bank</div><?= icon_chip('bank', 'red') ?></div>
        <div class="num mt-1 font-display text-xl font-bold"><?= money($s['spent_bank']) ?></div>
    </div>
    <?php $netCash = $s['cash'] - $s['spent_cash']; $netBank = $s['bank'] - $s['spent_bank']; ?>
    <div class="card card-pad">
        <div class="flex items-start justify-between"><div class="text-sm text-muted">Net cash movement</div><?= icon_chip('banknote', $netCash < 0 ? 'red' : 'green') ?></div>
        <div class="num mt-1 font-display text-xl font-bold <?= $netCash < 0 ? 'text-red-700 dark:text-red-300' : '' ?>"><?= money($netCash) ?></div>
        <div class="mt-1 text-xs text-muted">Cash received less cash spent</div>
    </div>
    <div class="card card-pad">
        <div class="flex items-start justify-between"><div class="text-sm text-muted">Net bank movement</div><?= icon_chip('bank', $netBank < 0 ? 'red' : 'green') ?></div>
        <div class="num mt-1 font-display text-xl font-bold <?= $netBank < 0 ? 'text-red-700 dark:text-red-300' : '' ?>"><?= money($netBank) ?></div>
        <div class="mt-1 text-xs text-muted">Bank received less bank spent</div>
    </div>
</div>
<p class="mt-2 text-xs text-muted">Spending is for the same dates. Pick where each expense was paid from when you record it under Expenses.</p>

<section class="card card-pad mt-6">
    <h2 class="panel-title">Received over time</h2>
    <p class="text-sm text-muted">Cash and bank receipts per <?= count($s['series']['labels']) > 62 ? 'month' : 'day' ?>.</p>
    <div class="mt-4 h-64"><canvas id="cashChart" data-series='<?= e(json_encode($s['series'])) ?>'></canvas></div>
</section>

<section class="card mt-6">
    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
        <h2 class="panel-title">Payments</h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($methodTabs as $k => $l): ?><a class="tab <?= $method === $k ? 'tab-active' : '' ?>" href="<?= $qs(['method' => $k]) ?>"><?= e($l) ?></a><?php endforeach; ?>
        </div>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Date</th><th>Invoice</th><th>Customer</th><th>Method</th><th>Reference</th><th>Received by</th><th class="text-right">Amount</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="whitespace-nowrap"><?= e(fdate($r['paid_at'], 'd M Y, g:i A')) ?></td>
                    <td><a class="font-medium text-link hover:underline" href="<?= url('/jobs/' . $r['job_id']) ?>"><?= e($r['invoice_number'] ?: '#' . $r['job_id']) ?></a></td>
                    <td><?= e($r['customer'] ?? '—') ?></td>
                    <td><span class="badge <?= $r['method'] === 'cash' ? 'badge-paid' : 'badge-washing' ?>"><?= e($methodLabel($r['method'])) ?></span></td>
                    <td class="text-muted"><?= e($r['reference'] ?: '—') ?></td>
                    <td class="text-muted"><?= e($r['received_by_name'] ?? '—') ?></td>
                    <td class="num text-right font-semibold"><?= money($r['amount']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="7" class="py-12 text-center text-muted">No payments in this period.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php \App\Core\View::partial('partials/pagination', ['pager' => $pager]); ?>
</section>
