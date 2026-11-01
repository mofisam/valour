<?php
/** @var array $job @var array $biz */
$forPdf = $forPdf ?? false;
$ink = '#11293B'; $accent = '#1F6F78';
$isPaid = (float) $job['total'] > 0 && (float) $job['total'] - (float) $job['amount_paid'] < 0.005;
require APP_PATH . '/Views/invoices/_styles.php';
?>
<style>.doc.modern { padding: 0; } .doc.modern .d-inner { padding: 26px 38px 34px; }</style>
<?php if (!$forPdf): ?>
<div class="noprint">
  <a href="<?= url('/jobs/' . $job['id']) ?>">Back to job</a>
  <a href="<?= url('/invoices/' . $job['id'] . '/pdf') ?>">Download PDF</a>
  <button onclick="window.print()">Print</button>
</div>
<?php endif; ?>
<div class="doc modern">
  <table style="background:#11293B;color:#fff"><tr>
    <td style="padding:24px 38px;vertical-align:top">
      <?php if (!empty($biz['logo']) && !$forPdf): ?><img src="<?= url($biz['logo']) ?>" alt="" style="height:46px;background:#fff;border-radius:4px;margin-bottom:8px"><br><?php endif; ?>
      <div style="font-size:18px;font-weight:bold"><?= e($biz['name']) ?></div>
      <div style="color:#c9d6e0"><?= nl2br(e($biz['address'])) ?></div>
      <div style="color:#c9d6e0"><?= e($biz['phone']) ?><?= $biz['email'] ? ' · ' . e($biz['email']) : '' ?></div>
    </td>
    <td class="r" style="padding:24px 38px;vertical-align:top">
      <div style="font-size:26px;font-weight:bold"><?= $isPaid ? 'Receipt' : 'Invoice' ?></div>
      <div style="font-weight:bold"><?= e($job['invoice_number']) ?></div>
      <div style="color:#c9d6e0"><?= e(fdate($job['job_date'])) ?></div>
    </td>
  </tr></table>
  <div style="height:5px;background:#B98A2E"></div>
  <div class="d-inner" style="padding:22px 38px 32px">
    <?php require APP_PATH . '/Views/invoices/_body.php'; ?>
  </div>
</div>
