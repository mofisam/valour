<?php
// Staff only see Dashboard and Jobs. Customers and Expenses belong to the admin.
$items = [
    ['/',           'Dashboard',  'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
    ['/jobs',       'Jobs',       'M9 5h6M9 9h6M9 13h4M6 3h12a1 1 0 011 1v16a1 1 0 01-1 1H6a1 1 0 01-1-1V4a1 1 0 011-1z'],
];
if (is_admin()) {
    $items[] = ['/customers', 'Customers', 'M16 11a4 4 0 10-8 0 4 4 0 008 0zM4 21c0-4 4-6 8-6s8 2 8 6'];
    $items[] = ['/expenses',  'Expenses',  'M12 3v18M17 7H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6'];
}
$adminItems = [
    ['/notifications','Notifications','M3 8l9 6 9-6M5 5h14a2 1 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z'],
    ['/reminders','Reminders',        'M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9zM13.7 21a2 2 0 01-3.4 0'],
    ['/receivables','Receivables',    'M3 7h18v12H3zM3 11h18M7 15h3'],
    ['/reports',  'Reports',          'M4 20V10M10 20V4M16 20v-7M22 20H2'],
    ['/activity', 'Activity log',     'M12 8v5l3 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ['/services', 'Services & prices','M20 12l-8 8-9-9V3h8l9 9zM7.5 7.5h.01'],
    ['/users',    'Staff',            'M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2M10 10a4 4 0 100-8 4 4 0 000 8zM21 20v-2a4 4 0 00-3-3.9'],
];
$icon = fn($d) => '<svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="' . $d . '"/></svg>';
$dueCount = is_admin() ? \App\Models\Reminder::dueCount((int) \App\Core\Auth::id()) : 0;
$noteCount = is_admin() ? \App\Models\Notification::unreadCount((int) \App\Core\Auth::id()) : 0;
?>
<nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="Main">
    <?php foreach ($items as [$href, $label, $d]): ?>
        <a href="<?= url($href) ?>" class="nav-link <?= is_active($href) ? 'nav-link-active' : '' ?>"><?= $icon($d) ?><?= e($label) ?></a>
    <?php endforeach; ?>
    <?php if (is_admin()): ?>
        <div class="my-3 border-t border-white/10"></div>
        <?php foreach ($adminItems as [$href, $label, $d]): ?>
            <a href="<?= url($href) ?>" class="nav-link <?= is_active($href) ? 'nav-link-active' : '' ?>">
                <?= $icon($d) ?><span class="flex-1"><?= e($label) ?></span>
                <?php if ($href === '/notifications'): ?>
                    <span data-notification-count class="<?= $noteCount ? '' : 'hidden' ?> num rounded-full bg-brass px-2 py-0.5 text-xs font-bold text-ink"><?= $noteCount ?></span>
                <?php endif; ?>
                <?php if ($href === '/reminders'): ?>
                    <span data-reminder-count class="<?= $dueCount ? '' : 'hidden' ?> num rounded-full bg-brass px-2 py-0.5 text-xs font-bold text-ink"><?= $dueCount ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
    <div class="my-3 border-t border-white/10"></div>
    <a href="<?= url('/settings') ?>" class="nav-link <?= is_active('/settings') ? 'nav-link-active' : '' ?>">
        <?= $icon('M12 15a3 3 0 100-6 3 3 0 000 6zM19 12a7 7 0 00-.1-1.2l2-1.5-2-3.4-2.3.9a7 7 0 00-2-1.2L14 3h-4l-.6 2.6a7 7 0 00-2 1.2l-2.3-.9-2 3.4 2 1.5a7 7 0 000 2.4l-2 1.5 2 3.4 2.3-.9a7 7 0 002 1.2L10 21h4l.6-2.6a7 7 0 002-1.2l2.3.9 2-3.4-2-1.5c.1-.4.1-.8.1-1.2z') ?>Settings
    </a>
</nav>
<div class="border-t border-white/10 px-5 py-4 text-xs text-white/60">
    Signed in as<br><span class="font-semibold text-white"><?= e(auth()['name'] ?? '') ?></span> · <?= e(auth()['role_label'] ?? '') ?>
</div>
