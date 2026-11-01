<?php $due = is_admin() ? \App\Models\Reminder::dueCount((int) \App\Core\Auth::id()) + \App\Models\Notification::unreadCount((int) \App\Core\Auth::id()) : 0; ?>
<header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-line bg-surface/90 px-4 backdrop-blur lg:px-8">
    <button type="button" id="sidebar-open" class="rounded-md p-2 text-fg hover:bg-canvas2 lg:hidden" aria-label="Open menu">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>
    <div class="hidden text-sm text-muted lg:block"><?= e(date('l, j F Y')) ?></div>
    <div class="flex items-center gap-2 sm:gap-3">
        <?php if (is_admin()): ?>
            <a href="<?= url('/notifications') ?>" class="relative rounded-md p-2 text-fg hover:bg-canvas2" aria-label="Notifications and reminders">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9zM13.7 21a2 2 0 01-3.4 0"/></svg>
                <span data-alert-count class="<?= $due ? '' : 'hidden' ?> num absolute -right-0.5 -top-0.5 min-w-[1.1rem] rounded-full bg-red-600 px-1 text-center text-[11px] font-bold leading-[1.1rem] text-white"><?= $due ?></span>
            </a>
        <?php endif; ?>
        <button type="button" data-theme-toggle class="rounded-md p-2 text-fg hover:bg-canvas2" aria-label="Switch between light and dark mode">
            <svg class="hidden h-5 w-5 dark:block" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            <svg class="h-5 w-5 dark:hidden" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>
        </button>
        <a href="<?= url('/jobs/create') ?>" class="btn-accent btn-sm">New job</a>
        <form method="post" action="<?= url('/logout') ?>"><?= csrf_field() ?><button class="btn-ghost btn-sm" type="submit">Sign out</button></form>
    </div>
</header>
