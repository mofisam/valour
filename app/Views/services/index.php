<?php
// The optional steps come from Process steps, which the admin can add to or remove from
$stepLabels = array_map(fn($st) => $st['label'], array_filter(job_steps(), fn($st) => !$st['locked']));
$firstLabel = job_steps()['pending']['label'] ?? 'Pending';
$lastLabel = job_steps()['delivered']['label'] ?? 'Delivered';
$perItem = array_values(array_filter($services, fn($s) => $s['pricing_model'] === 'per_item' && $s['is_active']));
?>
<h1 class="mb-1 font-display text-2xl font-bold">Services &amp; prices</h1>
<p class="mb-6 max-w-3xl text-sm text-muted">Each service has its own <strong>process</strong> (the statuses a job moves through) and its own <strong>price list</strong>.
    Staff never type prices: when they submit a job it has no price until you set it, and the price list below fills it in.</p>

<nav class="mb-6 flex flex-wrap gap-2" aria-label="On this page">
    <a class="tab" href="<?= url('/process-steps') ?>"><?= icon('layers', 'h-4 w-4') ?> Process steps</a>
    <a class="tab" href="#services">Services &amp; process</a>
    <a class="tab" href="#prices">Price list</a>
    <a class="tab" href="#clothing">Clothing types</a>
</nav>

<section id="services" class="card mb-8 scroll-mt-20">
    <h2 class="panel-title px-5 pt-4">Services &amp; process</h2>
    <p class="px-5 pb-2 text-sm text-muted">Services can be charged per item, per kg, or per hour for customers who use your machines for as long as they want. Tick the steps this service goes through. <em>Pending</em> and <em>Delivered</em> are always included. <a class="text-link underline" href="<?= url('/process-steps') ?>">Add, rename or remove steps</a>. For example, ironing only: untick Washing.</p>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Name</th><th>Charged</th><th>Rate</th><th>Process steps</th><th>Active</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($services as $s): $on = \App\Models\Service::workflowOf($s); ?>
                <tr>
                    <td><form method="post" action="<?= url('/services/' . $s['id']) ?>" id="svc-<?= $s['id'] ?>"><?= csrf_field() ?></form>
                        <input form="svc-<?= $s['id'] ?>" class="input" name="name" value="<?= e($s['name']) ?>" aria-label="Service name"></td>
                    <td><select form="svc-<?= $s['id'] ?>" class="input" name="pricing_model" aria-label="Pricing model">
                        <option value="per_item" <?= $s['pricing_model'] === 'per_item' ? 'selected' : '' ?>>Per item</option>
                        <option value="per_kg" <?= $s['pricing_model'] === 'per_kg' ? 'selected' : '' ?>>Per kg</option>
                        <option value="per_hour" <?= $s['pricing_model'] === 'per_hour' ? 'selected' : '' ?>>Per hour (machine time)</option></select></td>
                    <td><?php $rate = $s['pricing_model'] === 'per_hour' ? $s['price_per_hour'] : $s['price_per_kg']; ?>
                        <input form="svc-<?= $s['id'] ?>" class="input w-28" type="number" step="0.01" min="0" name="rate" value="<?= e($rate) ?>" aria-label="Rate per kg or per hour" <?= $s['pricing_model'] === 'per_item' ? 'placeholder="n/a"' : '' ?>></td>
                    <td>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                            <span class="text-muted"><?= e($firstLabel) ?></span>
                            <?php foreach ($stepLabels as $k => $l): ?>
                                <label class="inline-flex items-center gap-1.5"><input form="svc-<?= $s['id'] ?>" type="checkbox" name="steps[]" value="<?= $k ?>" <?= in_array($k, $on, true) ? 'checked' : '' ?>> <?= $l ?></label>
                            <?php endforeach; ?>
                            <span class="text-muted"><?= e($lastLabel) ?></span>
                        </div>
                    </td>
                    <td><input form="svc-<?= $s['id'] ?>" type="checkbox" name="is_active" value="1" <?= $s['is_active'] ? 'checked' : '' ?> aria-label="Active"></td>
                    <td><button form="svc-<?= $s['id'] ?>" class="btn-ghost btn-sm" type="submit">Save</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="post" action="<?= url('/services') ?>" class="grid gap-3 border-t border-line p-5 md:grid-cols-4">
        <?= csrf_field() ?>
        <input class="input md:col-span-2" name="name" placeholder="New service name" required aria-label="New service name">
        <select class="input" name="pricing_model" aria-label="Pricing model"><option value="per_item">Per item</option><option value="per_kg">Per kg</option><option value="per_hour">Per hour (machine time)</option></select>
        <input class="input" type="number" step="0.01" min="0" name="rate" placeholder="Rate (per kg or per hour)" aria-label="Rate per kg or per hour">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm md:col-span-4">
            <span class="text-muted">Steps:</span>
            <?php foreach ($stepLabels as $k => $l): ?><label class="inline-flex items-center gap-1.5"><input type="checkbox" name="steps[]" value="<?= $k ?>" <?= in_array($k, ['washing', 'ready'], true) ? 'checked' : '' ?>> <?= $l ?></label><?php endforeach; ?>
        </div>
        <button class="btn-primary md:col-span-4" type="submit">Add service</button>
    </form>
</section>

<section id="prices" class="card mb-8 scroll-mt-20">
    <h2 class="panel-title px-5 pt-4">Price list</h2>
    <p class="px-5 pb-2 text-sm text-muted">The price of each item for each service. This is what a job is priced from. Leave a box empty to use the item's fallback price (shown faintly).
        Per-kg services are priced by weight and per-hour (machine time) services by the time used, using the rate above.</p>
    <?php if (!$perItem): ?>
        <p class="p-5 text-sm text-muted">Add a per-item service first.</p>
    <?php else: ?>
    <form method="post" action="<?= url('/services/prices') ?>">
        <?= csrf_field() ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Item</th><?php foreach ($perItem as $s): ?><th class="text-right"><?= e($s['name']) ?></th><?php endforeach; ?></tr></thead>
                <tbody>
                <?php foreach ($clothingTypes as $t): if (!$t['is_active']) continue; ?>
                    <tr>
                        <td class="font-medium"><?= e($t['display_name'] ?: $t['name']) ?></td>
                        <?php foreach ($perItem as $s): $v = $priceMap[(int) $s['id']][(int) $t['id']] ?? null; ?>
                            <td class="text-right"><input class="input num w-28 text-right" type="number" step="0.01" min="0" name="prices[<?= (int) $s['id'] ?>][<?= (int) $t['id'] ?>]"
                                value="<?= $v !== null ? e($v) : '' ?>" placeholder="<?= (float) $t['price_per_item'] > 0 ? e(number_format((float) $t['price_per_item'], 2, '.', '')) : '0.00' ?>" aria-label="<?= e($s['name'] . ' price for ' . ($t['display_name'] ?: $t['name'])) ?>"></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="border-t border-line p-5"><button class="btn-primary" type="submit">Save price list</button></div>
    </form>
    <?php endif; ?>
</section>

<section id="clothing" class="card scroll-mt-20">
    <h2 class="panel-title px-5 pt-4">Clothing types</h2>
    <p class="px-5 pb-2 text-sm text-muted">The fallback price is used only when a service has no specific price for the item.</p>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Code</th><th>Shown as</th><th>Fallback price</th><th>Active</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($clothingTypes as $t): ?>
                <tr>
                    <td><form method="post" action="<?= url('/clothing-types/' . $t['id']) ?>" id="ct-<?= $t['id'] ?>"><?= csrf_field() ?></form>
                        <input form="ct-<?= $t['id'] ?>" class="input" name="name" value="<?= e($t['name']) ?>" aria-label="Code"></td>
                    <td><input form="ct-<?= $t['id'] ?>" class="input" name="display_name" value="<?= e($t['display_name']) ?>" aria-label="Display name"></td>
                    <td><input form="ct-<?= $t['id'] ?>" class="input w-28" type="number" step="0.01" min="0" name="price_per_item" value="<?= e($t['price_per_item']) ?>" aria-label="Fallback price"></td>
                    <td><input form="ct-<?= $t['id'] ?>" type="checkbox" name="is_active" value="1" <?= $t['is_active'] ? 'checked' : '' ?> aria-label="Active"></td>
                    <td><button form="ct-<?= $t['id'] ?>" class="btn-ghost btn-sm" type="submit">Save</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="post" action="<?= url('/clothing-types') ?>" class="grid gap-3 border-t border-line p-5 sm:grid-cols-3">
        <?= csrf_field() ?>
        <input class="input" name="name" placeholder="Code, e.g. jacket" required aria-label="Code">
        <input class="input" name="display_name" placeholder="Shown as, e.g. Jacket" aria-label="Display name">
        <input class="input" type="number" step="0.01" min="0" name="price_per_item" placeholder="Fallback price" required aria-label="Fallback price">
        <button class="btn-primary sm:col-span-3" type="submit">Add clothing type</button>
    </form>
</section>
