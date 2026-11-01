<?php
\App\Core\View::script('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js');
\App\Core\View::script('reports.js');
$s = $summary;
$cards = [
    ['Total jobs',      number_format($s['jobs']),        'Jobs created in this period',                       'clipboard',    'ink'],
    ['Delivered',       number_format($s['delivered']),   $s['jobs'] ? round($s['delivered'] / $s['jobs'] * 100) . '% of jobs' : 'No jobs yet', 'check-circle', 'green'],
    ['Total revenue',   money($s['revenue']),             'Billed on those jobs',                              'trending-up',  'sea'],
    ['Average revenue', money($s['avg_revenue']),         'Per job',                                           'bar-chart',    'brass'],
    ['Receivables',     money($s['receivables']),         'Billed but not yet paid',                           'file-text',    'red'],
    ['Total expenses',  money($s['expenses']),            'Cash ' . money($s['expenses_cash']) . ' · Bank ' . money($s['expenses_bank']),                            'credit-card',  'red'],
    ['Bank / Cash',     money($s['cash_total']),          'Cash ' . money($s['cash_cash']) . ' · Bank ' . money($s['cash_bank']), 'wallet', 'brass'],
    ['Gross profit',    money($s['gross_profit']),        'Revenue less direct costs (' . money($s['direct_costs']) . ')',        'percent', 'green'],
];
$neg = fn(float $v) => $v < 0 ? 'text-red-700 dark:text-red-300' : '';
?>
<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="flex items-center gap-2 font-display text-2xl font-bold"><?= icon('bar-chart', 'h-6 w-6 text-sea') ?> Reports</h1>
        <p class="text-sm text-muted"><?= e(fdate($from)) ?> to <?= e(fdate($to)) ?></p>
    </div>
    <form method="get" class="flex flex-wrap items-end gap-3">
        <div><label class="label" for="from">From</label><input class="input" type="date" id="from" name="from" value="<?= e($from) ?>"></div>
        <div><label class="label" for="to">To</label><input class="input" type="date" id="to" name="to" value="<?= e($to) ?>"></div>
        <button class="btn-primary" type="submit">Show</button>
        <a class="btn-ghost" href="<?= url('/cash?from=' . urlencode($from) . '&to=' . urlencode($to)) ?>"><?= icon('wallet', 'h-4 w-4') ?> Bank &amp; Cash</a>
        <a class="btn-ghost" href="<?= url('/reports/export?from=' . urlencode($from) . '&to=' . urlencode($to)) ?>"><?= icon('download', 'h-4 w-4') ?> Payments CSV</a>
    </form>
</div>

<div class="mb-4 flex flex-wrap gap-2 text-sm">
    <?php foreach ([
        'This month' => [date('Y-m-01'), date('Y-m-d')],
        'Last month' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('first day of last month'))],
        'This year'  => [date('Y-01-01'), date('Y-m-d')],
    ] as $label => [$a, $b]): ?>
        <a class="tab <?= ($from === $a && $to === $b) ? 'tab-active' : '' ?>" href="<?= url('/reports?from=' . $a . '&to=' . $b) ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <?php foreach ($cards as [$label, $value, $sub, $ic, $tone]): ?>
        <div class="card card-pad">
            <div class="flex items-start justify-between"><div class="text-sm text-muted"><?= e($label) ?></div><?= icon_chip($ic, $tone) ?></div>
            <div class="num mt-1 font-display text-2xl font-bold <?= $label === 'Gross profit' ? $neg($s['gross_profit']) : '' ?>"><?= e($value) ?></div>
            <div class="mt-1 text-xs text-muted"><?= e($sub) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <section class="card card-pad xl:col-span-2">
        <h2 class="panel-title flex items-center gap-2"><?= icon('trending-up', 'h-5 w-5 text-muted') ?> Revenue over time</h2>
        <p class="text-sm text-muted">Billed per <?= e($s['trend']['unit']) ?>.</p>
        <div class="mt-4 h-64"><canvas id="revenueChart"></canvas></div>
    </section>

    <section class="card card-pad">
        <h2 class="panel-title flex items-center gap-2"><?= icon('check-circle', 'h-5 w-5 text-muted') ?> Collection rate</h2>
        <div class="num mt-3 font-display text-3xl font-bold"><?= number_format($s['collection_rate'], 0) ?>%</div>
        <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-canvas2" role="progressbar" aria-valuenow="<?= (int) $s['collection_rate'] ?>" aria-valuemin="0" aria-valuemax="100"><div class="h-full rounded-full bg-sea" style="width:<?= (float) $s['collection_rate'] ?>%"></div></div>
        <p class="mt-2 text-sm text-muted"><?= money($s['revenue'] - $s['receivables']) ?> of <?= money($s['revenue']) ?> revenue has been paid.</p>
        <?php if ($s['receivables'] > 0): ?><a class="mt-4 inline-block text-sm font-medium text-link hover:underline" href="<?= url('/receivables') ?>">See who still owes (<?= money($s['receivables']) ?>)</a><?php endif; ?>
    </section>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <section class="card card-pad xl:col-span-2">
        <h2 class="panel-title flex items-center gap-2"><?= icon('credit-card', 'h-5 w-5 text-muted') ?> Revenue vs expenses</h2>
        <p class="text-sm text-muted">Per <?= e($s['trend']['unit']) ?>. Where the brass bar is taller than the teal one, that period lost money.</p>
        <div class="mt-4 h-64"><canvas id="compareChart"></canvas></div>
    </section>

    <section class="card card-pad">
        <h2 class="panel-title flex items-center gap-2"><?= icon('percent', 'h-5 w-5 text-muted') ?> Profit &amp; loss</h2>
        <table class="num mt-3 w-full text-sm">
            <tbody>
                <tr><td class="py-1.5">Revenue</td><td class="text-right"><?= money($s['revenue']) ?></td></tr>
                <tr><td class="py-1.5 text-muted">Less: direct costs</td><td class="text-right text-muted">(<?= money($s['direct_costs']) ?>)</td></tr>
                <tr class="border-t border-line font-semibold"><td class="py-1.5">Gross profit</td><td class="text-right <?= $neg($s['gross_profit']) ?>"><?= money($s['gross_profit']) ?></td></tr>
                <tr><td class="py-1.5 text-muted">Less: operating expenses</td><td class="text-right text-muted">(<?= money($s['operating']) ?>)</td></tr>
                <tr class="border-t-2 border-fg font-bold"><td class="py-2">Net profit</td><td class="text-right <?= $neg($s['net_profit']) ?>"><?= money($s['net_profit']) ?></td></tr>
            </tbody>
        </table>
        <p class="hint mt-3">Direct costs are the expense categories marked as "cost of service". Change them under Expenses → Manage categories.</p>
    </section>
</div>

<section class="card card-pad mt-6">
    <h2 class="panel-title flex items-center gap-2"><?= icon('clipboard', 'h-5 w-5 text-muted') ?> Jobs over time</h2>
    <p class="text-sm text-muted">Number of jobs per <?= e($s['trend']['unit']) ?>.</p>
    <div class="mt-4 h-64"><canvas id="jobsChart"></canvas></div>
</section>
<script type="application/json" id="trend-data"><?= json_encode($s['trend']) ?></script>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="card">
        <h2 class="panel-title px-5 py-4">Revenue by service</h2>
        <table class="table"><thead><tr><th>Service</th><th class="text-right">Jobs</th><th class="text-right">Revenue</th></tr></thead><tbody>
        <?php foreach ($s['by_service'] as $r): ?><tr><td><?= e($r['name']) ?></td><td class="num text-right"><?= (int) $r['jobs'] ?></td><td class="num text-right"><?= money($r['billed']) ?></td></tr><?php endforeach; ?>
        <?php if (!$s['by_service']): ?><tr><td colspan="3" class="py-6 text-center text-muted">No jobs in this range.</td></tr><?php endif; ?>
        </tbody></table>
    </section>
    <section class="card">
        <h2 class="panel-title px-5 py-4">Bank / Cash received</h2>
        <table class="table"><thead><tr><th>Method</th><th class="text-right">Payments</th><th class="text-right">Total</th></tr></thead><tbody>
        <?php foreach ($s['by_method'] as $r): ?><tr><td><?= e($r['method'] === 'cash' ? 'Cash' : ucfirst($r['method']) . ' (bank)') ?></td><td class="num text-right"><?= (int) $r['payments'] ?></td><td class="num text-right"><?= money($r['total']) ?></td></tr><?php endforeach; ?>
        <?php if (!$s['by_method']): ?><tr><td colspan="3" class="py-6 text-center text-muted">No payments in this range.</td></tr><?php endif; ?>
        </tbody></table>
    </section>
    <section class="card">
        <h2 class="panel-title px-5 py-4">Expenses by category</h2>
        <table class="table"><thead><tr><th>Category</th><th>Type</th><th class="text-right">Total</th></tr></thead><tbody>
        <?php foreach ($s['by_category'] as $r): ?><tr><td><?= e($r['name']) ?></td><td class="text-muted"><?= $r['is_direct'] ? 'Direct cost' : 'Operating' ?></td><td class="num text-right"><?= money($r['total']) ?></td></tr><?php endforeach; ?>
        <?php if (!$s['by_category']): ?><tr><td colspan="3" class="py-6 text-center text-muted">No expenses in this range.</td></tr><?php endif; ?>
        </tbody></table>
    </section>
    <section class="card">
        <h2 class="panel-title px-5 py-4">Top customers</h2>
        <table class="table"><thead><tr><th>Customer</th><th class="text-right">Jobs</th><th class="text-right">Revenue</th><th class="text-right">Paid</th></tr></thead><tbody>
        <?php foreach ($s['top_customers'] as $r): ?><tr><td><a class="text-link hover:underline" href="<?= url('/customers/' . $r['id']) ?>"><?= e($r['full_name']) ?></a></td><td class="num text-right"><?= (int) $r['jobs'] ?></td><td class="num text-right"><?= money($r['billed']) ?></td><td class="num text-right"><?= money($r['paid']) ?></td></tr><?php endforeach; ?>
        <?php if (!$s['top_customers']): ?><tr><td colspan="4" class="py-6 text-center text-muted">No customer activity in this range.</td></tr><?php endif; ?>
        </tbody></table>
    </section>
</div>
