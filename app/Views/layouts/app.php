<!DOCTYPE html>
<html lang="en">
<head><?php \App\Core\View::partial('partials/head', get_defined_vars()); ?></head>
<body>
<div class="min-h-screen lg:flex">
    <div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-ink/60 lg:hidden"></div>
    <!-- Sidebar stays put while the page scrolls (sticky + full viewport height on desktop) -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-side text-white transition-transform lg:sticky lg:top-0 lg:h-screen lg:shrink-0 lg:translate-x-0 lg:self-start">
        <a href="<?= url('/') ?>" class="flex items-center gap-3 px-5 py-5">
            <?php if ($logo = setting('business_logo_url')): ?>
                <img src="<?= url($logo) ?>" alt="" class="h-9 w-9 shrink-0 rounded bg-white object-contain">
            <?php else: ?>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded bg-brass font-display text-lg font-bold text-ink"><?= e(brand_initial()) ?></span>
            <?php endif; ?>
            <span class="font-display text-base font-bold leading-tight"><?= e(company_name()) ?></span>
        </a>
        <?php \App\Core\View::partial('partials/sidebar'); ?>
    </aside>

    <div class="min-w-0 flex-1">
        <?php \App\Core\View::partial('partials/navbar'); ?>
        <main class="mx-auto max-w-7xl px-4 py-6 lg:px-8">
            <?php \App\Core\View::partial('partials/flash'); ?>
            <?= $content ?>
        </main>
        <?php \App\Core\View::partial('partials/footer'); ?>
    </div>
</div>
<?php if (is_admin()): ?><div id="reminder-toasts" class="fixed bottom-4 right-4 z-[60] w-[calc(100%-2rem)] max-w-sm space-y-3" aria-live="assertive"></div><?php endif; ?>
<script>window.APP_BASE = <?= json_encode(base_path()) ?>; window.CSRF = <?= json_encode(csrf_token()) ?>; window.VE_ADMIN = <?= json_encode(is_admin()) ?>; window.CURRENCY = <?= json_encode(config('app.currency', '₦')) ?>; window.IDLE_SECONDS = <?= (int) \App\Core\Session::idleLimit() ?>;</script>
<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/idle.js') ?>"></script>
<?php if (is_admin()): ?><script src="<?= asset('js/reminders.js') ?>"></script><?php endif; ?>
<?php foreach (\App\Core\View::scripts() as $s): ?>
<script src="<?= str_starts_with($s, 'http') ? e($s) : asset('js/' . $s) ?>"></script>
<?php endforeach; ?>
</body>
</html>
