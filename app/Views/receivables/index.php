<?php $t = $totals; ?>
<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="font-display text-2xl font-bold">Receivables</h1>
        <p class="text-sm text-muted">Money customers still owe, grouped by how old the job is.</p>
    </div>
    <button type="button" class="btn-ghost" onclick="window.print()">Print</button>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <div class="card card-pad border-red-300 dark:border-red-500/40"><div class="text-sm text-muted">Total receivable</div><div class="num mt-1 font-display text-2xl font-bold text-red-700 dark:text-red-300"><?= money($t['total']) ?></div><div class="mt-1 text-xs text-muted"><?= count($rows) ?> customer<?= count($rows) === 1 ? '' : 's' ?></div></div>
    <?php foreach ([['0 – 30 days', 'b30'], ['31 – 60 days', 'b60'], ['61 – 90 days', 'b90'], ['Over 90 days', 'b90plus']] as [$label, $k]): ?>
        <div class="card card-pad"><div class="text-sm text-muted"><?= $label ?></div><div class="num mt-1 font-display text-xl font-bold <?= $k === 'b90plus' && $t[$k] > 0 ? 'text-red-700 dark:text-red-300' : '' ?>"><?= money($t[$k]) ?></div>
            <div class="mt-1 text-xs text-muted"><?= $t['total'] > 0 ? round($t[$k] / $t['total'] * 100) . '% of total' : '—' ?></div></div>
    <?php endforeach; ?>
</div>

<section class="card mt-6">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Customer</th><th class="text-right">Jobs</th><th class="text-right">0–30</th><th class="text-right">31–60</th><th class="text-right">61–90</th><th class="text-right">90+</th><th class="text-right">Total</th><th>Oldest job</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><a class="font-medium text-link hover:underline" href="<?= url('/customers/' . $r['id']) ?>"><?= e($r['full_name']) ?></a><div class="text-xs text-muted"><?= e($r['phone_number'] ?: '') ?></div></td>
                    <td class="num text-right"><?= (int) $r['jobs'] ?></td>
                    <?php foreach (['b30', 'b60', 'b90', 'b90plus'] as $k): ?><td class="num text-right <?= $r[$k] > 0 ? '' : 'text-muted' ?>"><?= $r[$k] > 0 ? money($r[$k]) : '—' ?></td><?php endforeach; ?>
                    <td class="num text-right font-semibold"><?= money($r['total']) ?></td>
                    <td class="whitespace-nowrap text-muted"><?= e(fdate($r['oldest'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="8" class="py-12 text-center text-muted">No outstanding receivables. Every job is paid for.</td></tr><?php endif; ?>
            </tbody>
            <?php if ($rows): ?>
            <tfoot><tr class="font-bold"><td class="px-4 py-3">Total</td><td></td>
                <?php foreach (['b30', 'b60', 'b90', 'b90plus'] as $k): ?><td class="num px-4 py-3 text-right"><?= money($t[$k]) ?></td><?php endforeach; ?>
                <td class="num px-4 py-3 text-right"><?= money($t['total']) ?></td><td></td></tr></tfoot>
            <?php endif; ?>
        </table>
    </div>
</section>
<p class="mt-3 text-xs text-muted">Age is counted from the job date. Cancelled jobs and drafts are not included.</p>
