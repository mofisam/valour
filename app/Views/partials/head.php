<?php
/** @var string $title */
$built = BASE_PATH . '/public/assets/css/app.css';
$useCompiled = is_file($built) && filesize($built) > 1000;
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="color-scheme" content="light dark">
<title><?= e($title ?? 'Dashboard') ?> · <?= e(company_name()) ?></title>
<?php if ($logo = setting('business_logo_url')): ?><link rel="icon" href="<?= url($logo) ?>"><?php endif; ?>
<script>
    // Apply the saved theme before first paint (prevents a white flash in dark mode).
    (function () {
        try {
            var t = localStorage.getItem('ve-theme') || 'system';
            var dark = t === 'dark' || (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        } catch (e) {}
    })();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php if ($useCompiled): ?>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<?php else: ?>
    <!-- Development fallback: Tailwind CDN. Run `npm run build` to produce app.css for production. -->
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="<?= asset('js/tailwind-config.js') ?>"></script>
    <style type="text/tailwindcss">
        <?= preg_replace('/@tailwind\s+\w+;/', '', (string) file_get_contents(BASE_PATH . '/public/assets/css/input.css')) ?>
    </style>
<?php endif; ?>
