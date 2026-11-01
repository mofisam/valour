<?php
$default = old('remind_at', date('Y-m-d\TH:i', strtotime('+1 hour')));
$repeatLabels = ['none' => 'Does not repeat', 'daily' => 'Every day', 'weekly' => 'Every week', 'monthly' => 'Every month'];

/** Row actions shared by the due and upcoming lists */
$actions = function (array $r): string {
    ob_start(); ?>
    <div class="mt-3 flex flex-wrap items-center gap-2">
        <form method="post" action="<?= url('/reminders/' . $r['id'] . '/done') ?>"><?= csrf_field() ?><button class="btn-primary btn-sm" type="submit">Mark done</button></form>
        <form method="post" action="<?= url('/reminders/' . $r['id'] . '/snooze') ?>" class="flex items-center gap-1">
            <?= csrf_field() ?>
            <label class="sr-only" for="sn<?= $r['id'] ?>">Snooze</label>
            <select id="sn<?= $r['id'] ?>" name="when" class="input w-auto py-1.5 text-xs">
                <option value="10m">10 minutes</option><option value="1h">1 hour</option><option value="tomorrow">Tomorrow 8 AM</option>
            </select>
            <button class="btn-ghost btn-sm" type="submit">Snooze</button>
        </form>
        <form method="post" action="<?= url('/reminders/' . $r['id'] . '/delete') ?>" data-confirm="Delete this reminder?"><?= csrf_field() ?><button class="btn-danger btn-sm" type="submit">Delete</button></form>
    </div>
    <?php return (string) ob_get_clean();
};
?>
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="font-display text-2xl font-bold">Reminders</h1>
        <p class="text-sm text-muted">Set a time and you'll get a pop-up and a bell alert when it arrives, as long as the app is open in your browser.</p>
    </div>
    <?php if (!empty($due)): ?><span class="badge badge-unpaid"><?= count($due) ?> due now</span><?php endif; ?>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <section class="card card-pad h-fit lg:col-span-1">
        <h2 class="panel-title">New reminder</h2>
        <form method="post" action="<?= url('/reminders') ?>" class="mt-4 space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="label" for="title">What should I remind you about?</label>
                <input class="input <?= error('title') ? 'input-error' : '' ?>" id="title" name="title" value="<?= e(old('title')) ?>" placeholder="Pay the detergent supplier" required>
                <?php if ($m = error('title')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="label" for="remind_at">Remind me on</label>
                <input class="input <?= error('remind_at') ? 'input-error' : '' ?>" type="datetime-local" id="remind_at" name="remind_at" value="<?= e($default) ?>" required>
                <?php if ($m = error('remind_at')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?>
                <div class="mt-2 flex flex-wrap gap-2" id="quick-times">
                    <button type="button" class="tab !px-2.5 !py-1 text-xs" data-add="60">In 1 hour</button>
                    <button type="button" class="tab !px-2.5 !py-1 text-xs" data-tomorrow="08:00">Tomorrow 8 AM</button>
                    <button type="button" class="tab !px-2.5 !py-1 text-xs" data-add="10080">Next week</button>
                </div>
            </div>
            <div>
                <label class="label" for="repeat_rule">Repeat</label>
                <select class="input" id="repeat_rule" name="repeat_rule">
                    <?php foreach ($repeatLabels as $k => $l): ?><option value="<?= $k ?>" <?= old('repeat_rule', 'none') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label" for="notes">Notes (optional)</label>
                <textarea class="input" rows="2" id="notes" name="notes"><?= e(old('notes')) ?></textarea>
            </div>
            <button class="btn-primary w-full" type="submit">Set reminder</button>
        </form>
        <hr class="my-5 border-line">
        <button type="button" class="btn-ghost btn-sm w-full" data-enable-notifications>Turn on desktop alerts</button>
        <p class="hint">Desktop alerts also appear when this tab is in the background.</p>
    </section>

    <div class="space-y-6 lg:col-span-2">
        <?php if ($due): ?>
        <section class="card border-brass">
            <h2 class="panel-title px-5 pt-4">Due now</h2>
            <ul class="divide-y divide-line">
                <?php foreach ($due as $r): ?>
                    <li class="p-5">
                        <div class="font-semibold"><?= e($r['title']) ?></div>
                        <div class="text-sm text-muted"><?= e(fdate($r['remind_at'], 'D j M, g:i A')) ?> · <?= e($repeatLabels[$r['repeat_rule']]) ?></div>
                        <?php if ($r['notes']): ?><p class="mt-1 text-sm"><?= e($r['notes']) ?></p><?php endif; ?>
                        <?= $actions($r) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>

        <section class="card">
            <h2 class="panel-title px-5 pt-4">Coming up</h2>
            <?php if (!$upcoming): ?>
                <p class="p-5 text-sm text-muted">Nothing scheduled. Add a reminder for a supplier payment, a staff review, or anything you don't want to forget.</p>
            <?php else: ?>
            <ul class="divide-y divide-line">
                <?php foreach ($upcoming as $r): ?>
                    <li class="p-5">
                        <div class="font-semibold"><?= e($r['title']) ?></div>
                        <div class="text-sm text-muted"><?= e(fdate($r['remind_at'], 'D j M, g:i A')) ?> · <?= e($repeatLabels[$r['repeat_rule']]) ?></div>
                        <?php if ($r['notes']): ?><p class="mt-1 text-sm"><?= e($r['notes']) ?></p><?php endif; ?>
                        <?= $actions($r) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </section>

        <?php if ($completed): ?>
        <details class="card">
            <summary class="cursor-pointer px-5 py-4 font-display text-lg font-bold">Completed (<?= count($completed) ?>)</summary>
            <ul class="divide-y divide-line border-t border-line text-sm">
                <?php foreach ($completed as $r): ?>
                    <li class="flex items-center justify-between gap-3 px-5 py-3">
                        <span class="text-muted line-through"><?= e($r['title']) ?></span>
                        <span class="shrink-0 text-xs text-muted"><?= e(fdate($r['completed_at'], 'j M, g:i A')) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </details>
        <?php endif; ?>
    </div>
</div>

<script>
    (function () {
        var input = document.getElementById('remind_at');
        var pad = function (n) { return String(n).padStart(2, '0'); };
        var fmt = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes()); };
        document.getElementById('quick-times').addEventListener('click', function (e) {
            var b = e.target.closest('button'); if (!b) return;
            var d = new Date();
            if (b.dataset.add) d = new Date(d.getTime() + Number(b.dataset.add) * 60000);
            if (b.dataset.tomorrow) { var t = b.dataset.tomorrow.split(':'); d.setDate(d.getDate() + 1); d.setHours(+t[0], +t[1], 0, 0); }
            input.value = fmt(d);
        });
    })();
</script>
