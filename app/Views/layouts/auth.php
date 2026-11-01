<!DOCTYPE html>
<html lang="en">
<head><?php \App\Core\View::partial('partials/head', get_defined_vars()); ?></head>
<body class="flex min-h-screen items-center justify-center px-4 py-10">
    <div class="w-full max-w-md">
        <div class="mb-6 flex items-center justify-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded bg-brass font-display text-lg font-bold text-ink"><?= e(brand_initial()) ?></span>
            <span class="font-display text-lg font-bold"><?= e(company_name()) ?></span>
        </div>
        <div class="card p-7 shadow-sm">
            <?php \App\Core\View::partial('partials/flash'); ?>
            <?= $content ?>
        </div>
    </div>
    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
