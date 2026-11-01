<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="font-display text-2xl font-bold">Notifications</h1>
        <p class="text-sm text-muted">Things that need your attention, such as jobs staff have submitted without a price.</p>
    </div>
    <?php if (array_filter($notifications, fn($n) => !$n['is_read'])): ?>
        <form method="post" action="<?= url('/notifications/read-all') ?>"><?= csrf_field() ?><button class="btn-ghost" type="submit">Mark all as read</button></form>
    <?php endif; ?>
</div>

<?php if ($dueReminders): ?>
    <a href="<?= url('/reminders') ?>" class="alert-warning mb-5 flex items-center justify-between gap-3 hover:opacity-90">
        <span><strong><?= count($dueReminders) ?> reminder<?= count($dueReminders) === 1 ? '' : 's' ?> due:</strong> <?= e($dueReminders[0]['title']) ?><?= count($dueReminders) > 1 ? ' and ' . (count($dueReminders) - 1) . ' more' : '' ?></span>
        <span class="font-semibold">Open reminders</span>
    </a>
<?php endif; ?>

<section class="card">
    <?php if (!$notifications): ?>
        <p class="p-8 text-center text-sm text-muted">You're all caught up. New notifications will appear here.</p>
    <?php else: ?>
    <ul class="divide-y divide-line">
        <?php foreach ($notifications as $n): ?>
            <li class="flex flex-wrap items-start justify-between gap-3 p-5 <?= $n['is_read'] ? 'opacity-60' : '' ?>">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 font-semibold">
                        <?php if (!$n['is_read']): ?><span class="h-2 w-2 shrink-0 rounded-full bg-red-600" aria-label="Unread"></span><?php endif; ?>
                        <?= e($n['title']) ?>
                    </div>
                    <?php if ($n['body']): ?><p class="mt-1 text-sm text-muted"><?= e($n['body']) ?></p><?php endif; ?>
                    <p class="mt-1 text-xs text-muted"><?= e(fdate($n['created_at'], 'D j M, g:i A')) ?></p>
                </div>
                <div class="flex gap-2">
                    <?php if ($n['link']): ?><a class="btn-primary btn-sm" href="<?= url($n['link']) ?>">Open</a><?php endif; ?>
                    <?php if (!$n['is_read']): ?><form method="post" action="<?= url('/notifications/' . $n['id'] . '/read') ?>"><?= csrf_field() ?><button class="btn-ghost btn-sm" type="submit">Mark read</button></form><?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</section>
