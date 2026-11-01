<?php
/**
 * Shared body of every invoice / receipt template.
 * Layout uses tables (not flexbox) so it prints identically and converts to PDF with dompdf.
 * @var array $job @var array $biz
 */
$perHour = ($job['pricing_model'] ?? '') === 'per_hour';
$perKg   = ($job['pricing_model'] ?? '') === 'per_kg';
$byValue = $perHour || $perKg;
$total   = (float) $job['total'];
$paid    = (float) $job['amount_paid'];
$balance = max(0.0, $total - $paid);
$isPaid  = $balance < 0.005 && $total > 0;
$isPart  = !$isPaid && $paid > 0.004;
$dur     = (int) ($job['duration_minutes'] ?? 0);
$durText = trim(($dur >= 60 ? intdiv($dur, 60) . ' h ' : '') . ($dur % 60 ? $dur % 60 . ' min' : ''));
$lastPay = $job['payments'] ? end($job['payments'])['paid_at'] : null;
?>
<?php if ($isPaid): ?>
<table class="d-status d-paid"><tr>
    <td class="d-status-label">PAID IN FULL</td>
    <td class="d-status-text">Thank you. <?= money($paid) ?> received<?= $lastPay ? ' on ' . e(fdate($lastPay)) : '' ?>. Nothing more is due.</td>
</tr></table>
<?php elseif ($isPart): ?>
<table class="d-status d-part"><tr>
    <td class="d-status-label">PART PAYMENT</td>
    <td class="d-status-text"><?= money($paid) ?> paid so far. <strong>Balance due: <?= money($balance) ?></strong></td>
</tr></table>
<?php else: ?>
<table class="d-status d-unpaid"><tr>
    <td class="d-status-label">NOT PAID</td>
    <td class="d-status-text">No payment has been received yet. <strong>Amount due: <?= money($balance) ?></strong></td>
</tr></table>
<?php endif; ?>

<table class="d-two">
    <tr>
        <td class="d-box">
            <div class="d-cap">Billed to</div>
            <div class="d-strong"><?= e($job['customer_name'] ?? 'Walk-in customer') ?></div>
            <?php if (!empty($job['phone_number'])): ?><div><?= e($job['phone_number']) ?></div><?php endif; ?>
            <?php if (!empty($job['customer_address'])): ?><div class="d-muted"><?= nl2br(e($job['customer_address'])) ?></div><?php endif; ?>
        </td>
        <td class="d-gap"></td>
        <td class="d-box">
            <div class="d-cap">Job details</div>
            <table class="d-kv">
                <tr><td>Service</td><td class="d-strong"><?= e($job['service_name'] ?? '—') ?></td></tr>
                <tr><td>Dropped off</td><td><?= e(fdate($job['job_date'])) ?></td></tr>
                <?php if ($job['due_date']): ?><tr><td>Ready by</td><td><?= e(fdate($job['due_date'])) ?></td></tr><?php endif; ?>
                <tr><td>Status</td><td><?= e(JOB_LABELS[$job['status']] ?? ucfirst($job['status'])) ?></td></tr>
                <?php if (!empty($job['created_by_name'])): ?><tr><td>Served by</td><td><?= e($job['created_by_name']) ?></td></tr><?php endif; ?>
            </table>
        </td>
    </tr>
</table>

<table class="d-items">
    <thead><tr>
        <th>Item</th><th>Colour</th><th>Length</th><th class="r">Qty</th>
        <?php if (!$byValue): ?><th class="r">Price</th><th class="r">Amount</th><?php endif; ?>
    </tr></thead>
    <tbody>
    <?php foreach ($job['items'] as $it): ?>
        <tr>
            <td><?= e($it['type_label']) ?></td>
            <td><?= e(option_label($it['color'])) ?></td>
            <td><?= e(option_label($it['length'])) ?></td>
            <td class="r"><?= (int) $it['quantity'] ?></td>
            <?php if (!$byValue): ?><td class="r"><?= money($it['price_per_unit']) ?></td><td class="r"><?= money($it['amount']) ?></td><?php endif; ?>
        </tr>
    <?php endforeach; ?>
    <?php if ($perKg): ?>
        <tr class="d-charge"><td colspan="4"><strong>Weight charge:</strong> <?= e(rtrim(rtrim((string) $job['total_kg'], '0'), '.')) ?> kg × <?= money($job['price_per_kg']) ?></td></tr>
    <?php elseif ($perHour): ?>
        <tr class="d-charge"><td colspan="4"><strong>Machine time:</strong> <?= e($durText) ?> × <?= money($job['price_per_hour']) ?> per hour</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<table class="d-two" style="margin-top:14px">
    <tr>
        <td class="d-left">
            <?php if ($job['payments']): ?>
                <div class="d-cap">Payments received</div>
                <table class="d-pay">
                    <?php foreach ($job['payments'] as $p): ?>
                        <tr><td><?= e(fdate($p['paid_at'])) ?></td><td><?= e($p['method'] === 'pos' ? 'POS' : ucfirst($p['method'])) ?><?= $p['reference'] ? ' · ' . e($p['reference']) : '' ?></td><td class="r"><?= money($p['amount']) ?></td></tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </td>
        <td class="d-gap"></td>
        <td class="d-right">
            <table class="d-totals">
                <tr><td>Subtotal</td><td class="r"><?= money($job['subtotal']) ?></td></tr>
                <?php if ((float) $job['discount_amount'] > 0): ?>
                    <tr><td>Discount<?= $job['discount_type'] === 'percent' ? ' (' . (float) $job['discount_value'] . '%)' : '' ?></td><td class="r">−<?= money($job['discount_amount']) ?></td></tr>
                <?php endif; ?>
                <tr class="d-total"><td>Total</td><td class="r"><?= money($total) ?></td></tr>
                <tr><td>Amount paid</td><td class="r"><?= money($paid) ?></td></tr>
                <tr class="d-balance <?= $isPaid ? 'ok' : 'due' ?>"><td><?= $isPaid ? 'Balance' : 'Balance due' ?></td><td class="r"><?= money($balance) ?></td></tr>
            </table>
        </td>
    </tr>
</table>

<?php if (!$isPaid && ($biz['bank'] || $biz['acc_no'])): ?>
<table class="d-pay-box"><tr><td>
    <div class="d-cap">How to pay</div>
    Transfer <strong><?= money($balance) ?></strong> to <strong><?= e($biz['bank']) ?></strong> · account <strong><?= e($biz['acc_no']) ?></strong><?= $biz['acc_name'] ? ' · ' . e($biz['acc_name']) : '' ?>.
    Quote <strong><?= e($job['invoice_number']) ?></strong> as the reference.
</td></tr></table>
<?php endif; ?>

<?php if ($job['notes']): ?><p class="d-notes"><span class="d-muted">Notes:</span> <?= nl2br(e($job['notes'])) ?></p><?php endif; ?>
<p class="d-foot"><?= e($biz['footer']) ?><br><span class="d-muted">Printed <?= e(date('j M Y, g:i A')) ?></span></p>
