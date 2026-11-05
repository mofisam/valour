<?php
$colors = array_keys(STEP_COLORS);
$nonLocked = array_filter($steps, fn($s) => !$s['is_locked']);
?>
<div class="mb-5">
    <a href="<?= url('/services') ?>" class="text-sm text-link hover:underline">Back to Services &amp; prices</a>
    <h1 class="flex items-center gap-2 font-display text-2xl font-bold"><?= icon('layers', 'h-6 w-6 text-sea') ?> Process steps</h1>
    <p class="mt-1 max-w-3xl text-sm text-muted">These are the statuses a job can move through. Add your own (for example <em>Folding</em> or <em>Packaging</em>), rename or recolour them, and change the order.
        Then tick the steps each service uses on the Services page. <strong>Pending</strong> and <strong>Delivered</strong> are part of every job and can't be removed.</p>
</div>

<section class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Order</th><th>Name</th><th>Colour</th><th>Used by</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($steps as $s): ?>
                <tr>
                    <td>
                        <form method="post" action="<?= url('/process-steps/' . $s['id']) ?>" id="st-<?= $s['id'] ?>"><?= csrf_field() ?></form>
                        <?php if ($s['is_locked']): ?>
                            <span class="text-xs text-muted"><?= $s['step_key'] === 'pending' ? 'First' : 'Last' ?></span>
                        <?php else: ?>
                            <input form="st-<?= $s['id'] ?>" class="input num w-20" type="number" min="1" max="89" name="sort_order" value="<?= (int) $s['sort_order'] ?>" aria-label="Order for <?= e($s['label']) ?>">
                        <?php endif; ?>
                    </td>
                    <td>
                        <input form="st-<?= $s['id'] ?>" class="input" name="label" maxlength="60" value="<?= e($s['label']) ?>" aria-label="Step name" required>
                        <?php if ($s['is_locked']): ?><div class="mt-1 text-xs text-muted">Always part of every job</div><?php endif; ?>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <select form="st-<?= $s['id'] ?>" class="input w-32" name="color" aria-label="Colour">
                                <?php foreach ($colors as $c): ?><option value="<?= $c ?>" <?= $s['color'] === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option><?php endforeach; ?>
                            </select>
                            <span class="badge <?= STEP_COLORS[$s['color']] ?? STEP_COLORS['slate'] ?>"><?= e($s['label']) ?></span>
                        </div>
                    </td>
                    <td class="text-sm">
                        <div><span class="num font-semibold"><?= (int) $s['jobs'] ?></span> <span class="text-muted">job<?= $s['jobs'] === 1 ? '' : 's' ?> now</span></div>
                        <div class="text-xs text-muted"><?= $s['is_locked'] ? 'All services' : ($s['services'] ? e(implode(', ', $s['services'])) : 'No service uses it yet') ?></div>
                    </td>
                    <td class="whitespace-nowrap text-right">
                        <button form="st-<?= $s['id'] ?>" class="btn-ghost btn-sm" type="submit">Save</button>
                        <?php if (!$s['is_locked']): ?>
                            <form class="inline" method="post" action="<?= url('/process-steps/' . $s['id'] . '/delete') ?>" data-confirm="Remove the step “<?= e($s['label']) ?>”? It will be taken out of every service.">
                                <?= csrf_field() ?><button class="btn-danger btn-sm" type="submit">Remove</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <form method="post" action="<?= url('/process-steps') ?>" class="grid gap-3 border-t border-line p-5 md:grid-cols-4">
        <?= csrf_field() ?>
        <div class="md:col-span-2">
            <label class="label" for="new_label">Add a step</label>
            <input class="input" id="new_label" name="label" maxlength="60" placeholder="e.g. Folding, Packaging, Stain treatment" required>
        </div>
        <div>
            <label class="label" for="new_after">Place after</label>
            <select class="input" id="new_after" name="after">
                <?php foreach ($steps as $s): if ($s['step_key'] === 'delivered') continue; ?><option value="<?= e($s['step_key']) ?>" <?= $s['step_key'] === 'ironing' ? 'selected' : '' ?>><?= e($s['label']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label" for="new_color">Colour</label>
            <select class="input" id="new_color" name="color"><?php foreach ($colors as $c): ?><option value="<?= $c ?>"><?= ucfirst($c) ?></option><?php endforeach; ?></select>
        </div>
        <button class="btn-primary md:col-span-4" type="submit"><?= icon('plus', 'h-4 w-4') ?> Add step</button>
    </form>
</section>
