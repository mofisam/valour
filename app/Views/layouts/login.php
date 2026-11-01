<!DOCTYPE html>
<html lang="en">
<head><?php \App\Core\View::partial('partials/head', get_defined_vars()); ?></head>
<body>
<div class="grid min-h-screen lg:grid-cols-[minmax(0,5fr)_minmax(0,4fr)]">
    <!-- Brand panel -->
    <section class="relative flex flex-col justify-between overflow-hidden bg-side px-8 py-8 text-white lg:px-14 lg:py-12">
        <div class="flex items-center gap-3">
            <?php if ($logo = setting('business_logo_url')): ?>
                <img src="<?= url($logo) ?>" alt="" class="h-11 w-11 rounded bg-white object-contain">
            <?php else: ?>
                <span class="flex h-11 w-11 items-center justify-center rounded bg-brass font-display text-xl font-bold text-ink"><?= e(brand_initial()) ?></span>
            <?php endif; ?>
            <span class="font-display text-lg font-bold"><?= e(company_name()) ?></span>
        </div>

        <div class="my-10 max-w-lg lg:my-0">
            <div class="mb-6 h-1 w-14 rounded bg-brass"></div>
            <h1 class="font-display text-3xl font-bold leading-tight sm:text-4xl lg:text-5xl"><?= e(setting('business_tagline', 'Every garment, tracked from drop-off to pickup.')) ?></h1>
            <p class="mt-5 text-base text-white/70">Sign in to log new jobs, take payments, and see who is waiting to collect.</p>
        </div>

        <p class="hidden text-sm text-white/50 lg:block">© <?= date('Y') ?> <?= e(company_name()) ?></p>
        <!-- decorative hanger-rail lines -->
        <svg class="pointer-events-none absolute -bottom-10 -right-10 h-72 w-72 text-white/[0.06]" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="6" aria-hidden="true"><circle cx="100" cy="100" r="90"/><circle cx="100" cy="100" r="60"/><circle cx="100" cy="100" r="30"/></svg>
    </section>

    <!-- Form panel -->
    <section class="relative flex items-center justify-center px-6 py-10">
        <button type="button" data-theme-toggle class="absolute right-4 top-4 rounded-md p-2 text-fg hover:bg-canvas2" aria-label="Switch between light and dark mode">
            <svg class="hidden h-5 w-5 dark:block" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            <svg class="h-5 w-5 dark:hidden" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>
        </button>
        <div class="w-full max-w-sm">
            <?php \App\Core\View::partial('partials/flash'); ?>
            <?= $content ?>
        </div>
    </section>
</div>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
