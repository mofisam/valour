<?php
/** @var array $job @var array $biz */
$forPdf = $forPdf ?? false;
$ink = '#11293B'; $accent = '#11293B';
$isPaid = (float) $job['total'] > 0 && (float) $job['total'] - (float) $job['amount_paid'] < 0.005;
require APP_PATH . '/Views/invoices/_styles.php';
?>
<?php if (!$forPdf): ?>
<div class="noprint">
  <a href="<?= url('/jobs/' . $job['id']) ?>">Back to job</a>
  <a href="<?= url('/invoices/' . $job['id'] . '/pdf') ?>">Download PDF</a>
  <button onclick="window.print()">Print</button>
</div>
<?php endif; ?>
<div class="doc">
  <table style="margin-bottom:18px"><tr>
    <td style="vertical-align:top">
      <?php if (!empty($biz['logo']) && !$forPdf): ?><img src="<?= url($biz['logo']) ?>" alt="" style="height:54px;margin-bottom:8px"><br><?php endif; ?>
      <div style="font-size:18px;font-weight:bold"><?= e($biz['name']) ?></div>
      <div class="d-muted"><?= nl2br(e($biz['address'])) ?></div>
      <div class="d-muted"><?= e($biz['phone']) ?><?= $biz['email'] ? ' · ' . e($biz['email']) : '' ?></div>
    </td>
    <td class="r" style="vertical-align:top">
      <div style="font-size:28px;font-weight:bold;letter-spacing:2px"><?= $isPaid ? 'RECEIPT' : 'INVOICE' ?></div>
      <div style="font-size:14px;font-weight:bold"><?= e($job['invoice_number']) ?></div>
      <div class="d-muted">Date: <?= e(fdate($job['job_date'])) ?></div>
    </td>
  </tr></table>
  <?php require APP_PATH . '/Views/invoices/_body.php'; ?>
</div>
