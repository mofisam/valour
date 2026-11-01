<?php /* Colours differ by template: $accent (headers/lines), $ink (text). Everything is plain CSS 2.1 for dompdf. */
$font = ($forPdf ?? false) ? "'DejaVu Sans', sans-serif" : "'Segoe UI', Arial, Helvetica, sans-serif"; ?>
<style>
  @page { size: A4; margin: 12mm; }
  .doc { font-family: <?= $font ?>; color: <?= $ink ?>; font-size: 12.5px; line-height: 1.45; max-width: 800px; margin: 22px auto; background: #fff; padding: 34px 38px; box-sizing: border-box; }
  .doc table { width: 100%; border-collapse: collapse; }
  .doc .r { text-align: right; }
  .doc .d-muted { color: #5b6b78; }
  .doc .d-strong { font-weight: bold; }
  .doc .d-cap { font-size: 10.5px; letter-spacing: .08em; text-transform: uppercase; color: #5b6b78; margin-bottom: 4px; font-weight: bold; }

  .doc .d-status { margin: 0 0 18px; border-collapse: separate; border-spacing: 0; }
  .doc .d-status td { padding: 11px 14px; vertical-align: middle; }
  .doc .d-status-label { width: 150px; font-weight: bold; letter-spacing: .06em; font-size: 14px; text-align: center; color: #fff; }
  .doc .d-status-text { font-size: 13px; }
  .doc .d-paid   { border: 2px solid #1b7a46; background: #e8f5ee; }  .doc .d-paid .d-status-label   { background: #1b7a46; }
  .doc .d-part   { border: 2px solid #b7791f; background: #fdf3df; }  .doc .d-part .d-status-label   { background: #b7791f; }
  .doc .d-unpaid { border: 2px solid #c0392b; background: #fdecea; }  .doc .d-unpaid .d-status-label { background: #c0392b; }

  .doc .d-two { border-collapse: separate; border-spacing: 0; }
  .doc .d-two > tbody > tr > td, .doc .d-two > tr > td { vertical-align: top; }
  .doc .d-box { width: 49%; border: 1px solid #d9e1e8; padding: 11px 13px; }
  .doc .d-gap { width: 2%; }
  .doc .d-left { width: 52%; } .doc .d-right { width: 46%; }
  .doc .d-kv td { padding: 1.5px 0; } .doc .d-kv td:first-child { color: #5b6b78; width: 38%; }

  .doc .d-items { margin-top: 16px; }
  .doc .d-items th { text-align: left; background: <?= $accent ?>; color: #fff; padding: 8px 8px; font-size: 11.5px; letter-spacing: .03em; }
  .doc .d-items th.r { text-align: right; }
  .doc .d-items td { padding: 8px; border-bottom: 1px solid #e3eaf0; }
  .doc .d-items .d-charge td { background: #f4f6f8; border-bottom: 1px solid #e3eaf0; }

  .doc .d-pay td { padding: 4px 0; border-bottom: 1px dotted #c9d3dc; font-size: 12px; }
  .doc .d-totals td { padding: 4px 0; }
  .doc .d-totals .d-total td { border-top: 1px solid #11293B; font-weight: bold; padding-top: 7px; font-size: 14px; }
  .doc .d-totals .d-balance td { font-weight: bold; font-size: 15px; padding-top: 6px; }
  .doc .d-totals .d-balance.due td { color: #c0392b; }
  .doc .d-totals .d-balance.ok td { color: #1b7a46; }

  .doc .d-pay-box { margin-top: 18px; border: 1px dashed <?= $accent ?>; background: #f8fafb; }
  .doc .d-pay-box td { padding: 11px 14px; }
  .doc .d-notes { margin-top: 14px; }
  .doc .d-foot { margin-top: 22px; padding-top: 10px; border-top: 1px solid #e3eaf0; color: #5b6b78; font-size: 11.5px; text-align: center; }

  .noprint { max-width: 800px; margin: 16px auto 0; text-align: right; font-family: Arial, sans-serif; }
  .noprint a, .noprint button { display: inline-block; background: #11293B; color: #fff; border: 0; padding: 8px 14px; border-radius: 4px; text-decoration: none; font-size: 13px; cursor: pointer; margin-left: 6px; }
  @media print {
    .noprint { display: none; }
    body { background: #fff !important; }
    .doc { margin: 0; padding: 0; max-width: none; }
    .doc .d-status-label, .doc .d-items th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .doc .d-paid, .doc .d-part, .doc .d-unpaid { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
