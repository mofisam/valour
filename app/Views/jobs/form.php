<?php
\App\Core\View::script('jobs.js');
$j = $job;
$isEdit = $j !== null;
$action = $isEdit ? url('/jobs/' . $j['id']) : url('/jobs');

// Item rows: old input (after a validation error) wins over saved items.
$rows = old('items', null);
if ($rows === null) {
    $rows = $isEdit ? array_map(fn($i) => [
        'clothing_type_id' => $i['clothing_type_id'], 'color' => $i['color'], 'length' => $i['length'],
        'quantity' => $i['quantity'], 'price_per_unit' => $i['price_per_unit'],
    ], $j['items']) : [];
}
$rows = array_values((array) $rows);

$val = fn(string $k, $default = '') => old($k, $j[$k] ?? $default);
$custId = old('customer_id', $j['customer_id'] ?? ($customer['id'] ?? ''));
$custName = old('customer_search', $customer['full_name'] ?? '');
$discType = $val('discount_type', 'none');
$mins = (int) ($j['duration_minutes'] ?? 0);
$durH = old('duration_hours', $mins ? intdiv($mins, 60) : '');
$durM = old('duration_mins', $mins ? $mins % 60 : '');
?>
<div class="mx-auto max-w-5xl">
    <h1 class="mb-5 font-display text-2xl font-bold"><?= $isEdit ? 'Edit ' . e($j['invoice_number'] ?: 'draft #' . $j['id']) : 'New job' ?></h1>

    <form id="job-form" method="post" action="<?= $action ?>" class="space-y-6"
          data-services='<?= e(json_encode($services)) ?>'
          data-types='<?= e(json_encode($clothingTypes)) ?>'
          data-prices='<?= e(json_encode((object) $prices)) ?>'
          data-rows='<?= e(json_encode($rows)) ?>'
          data-can-price="<?= is_admin() ? '1' : '0' ?>">
        <?= csrf_field() ?>

        <section class="card card-pad grid gap-4 md:grid-cols-2">
            <div class="relative md:col-span-2">
                <label class="label" for="customer_search">Customer</label>
                <input class="input <?= error('customer_id') ? 'input-error' : '' ?>" id="customer_search" name="customer_search" autocomplete="off"
                       value="<?= e($custName) ?>" placeholder="Type a name or phone number">
                <input type="hidden" id="customer_id" name="customer_id" value="<?= e($custId) ?>">
                <ul id="customer_results" class="absolute z-20 mt-1 hidden w-full overflow-hidden rounded-md border border-line bg-surface shadow-lg"></ul>
                <?php if ($m = error('customer_id')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?>
                <p id="customer_balance" class="alert-warning mt-2 hidden" data-owes="<?= e(is_admin() && $custId ? \App\Models\Customer::outstandingFor((int) $custId) : 0) ?>"></p>
                <?php if (is_admin()): ?><p class="hint">New customer? <a class="text-link underline" href="<?= url('/customers/create') ?>">Add them first</a>.</p>
                <?php else: ?><p class="hint">Only customers added by the admin can be selected.</p><?php endif; ?>
            </div>
            <div>
                <label class="label" for="service_id">Service</label>
                <select class="input <?= error('service_id') ? 'input-error' : '' ?>" id="service_id" name="service_id" required>
                    <option value="">Choose a service…</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (string) $val('service_id') === (string) $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['name']) ?><?= $s['pricing_model'] === 'per_kg' ? ' (per kg)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($m = error('service_id')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?>
                <p id="process-hint" class="hint"></p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label" for="job_date">Drop-off date</label>
                    <input class="input" type="date" id="job_date" name="job_date" value="<?= e($val('job_date', date('Y-m-d'))) ?>" required>
                </div>
                <div>
                    <label class="label" for="due_date">Ready by</label>
                    <input class="input" type="date" id="due_date" name="due_date" value="<?= e($val('due_date')) ?>">
                </div>
            </div>
        </section>

        <section class="card card-pad space-y-4">
            <div id="per-kg-box" class="hidden grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="total_kg">Weight (kg)</label>
                    <input class="input <?= error('total_kg') ? 'input-error' : '' ?>" type="number" step="0.001" min="0" id="total_kg" name="total_kg" value="<?= e($val('total_kg')) ?>">
                    <?php if ($m = error('total_kg')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?>
                </div>
                <div class="<?= is_admin() ? '' : 'hidden' ?>">
                    <label class="label" for="price_per_kg">Price per kg</label>
                    <input class="input" type="number" step="0.01" min="0" id="price_per_kg" name="price_per_kg" value="<?= e($val('price_per_kg')) ?>" <?= is_admin() ? '' : 'readonly' ?>>
                </div>
            </div>

            <div id="per-hour-box" class="hidden grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="label" for="duration_hours">Time on the machine</label>
                    <div class="flex items-center gap-2">
                        <input class="input w-24 <?= error('duration') ? 'input-error' : '' ?>" type="number" min="0" step="1" id="duration_hours" name="duration_hours" value="<?= e($durH) ?>" aria-label="Hours">
                        <span class="text-sm text-muted">hours</span>
                        <input class="input w-24" type="number" min="0" max="59" step="1" id="duration_mins" name="duration_mins" value="<?= e($durM) ?>" aria-label="Minutes">
                        <span class="text-sm text-muted">minutes</span>
                    </div>
                    <?php if ($m = error('duration')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?>
                    <p class="hint">For customers who use the machine for as long as they want. They are charged for the time used.</p>
                </div>
                <div class="<?= is_admin() ? '' : 'hidden' ?>">
                    <label class="label" for="price_per_hour">Rate per hour</label>
                    <input class="input" type="number" step="0.01" min="0" id="price_per_hour" name="price_per_hour" value="<?= e($val('price_per_hour')) ?>">
                </div>
            </div>

            <div id="items-box">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-display text-lg font-bold">Clothes <span id="items-optional" class="hidden text-sm font-normal text-muted">(optional for this service)</span></h2>
                    <button type="button" id="add-row" class="btn-ghost btn-sm">Add item</button>
                </div>
                <?php if ($m = error('items')): ?><p class="field-error mb-2"><?= e($m) ?></p><?php endif; ?>
                <div id="item-rows" class="space-y-3"></div>
                <template id="item-row-template"><?php require APP_PATH . '/Views/jobs/_item_row.php'; ?></template>
            </div>
        </section>

        <section class="grid gap-6 md:grid-cols-2">
            <div class="card card-pad space-y-4">
                <?php if (is_admin()): ?>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="discount_type">Discount</label>
                        <select class="input" id="discount_type" name="discount_type">
                            <option value="none" <?= $discType === 'none' ? 'selected' : '' ?>>None</option>
                            <option value="percent" <?= $discType === 'percent' ? 'selected' : '' ?>>Percent (%)</option>
                            <option value="fixed" <?= $discType === 'fixed' ? 'selected' : '' ?>>Fixed amount</option>
                        </select>
                    </div>
                    <div>
                        <label class="label" for="discount_value">Value</label>
                        <input class="input" type="number" step="0.01" min="0" id="discount_value" name="discount_value" value="<?= e($val('discount_value', '0')) ?>">
                    </div>
                </div>
                <?php else: ?>
                <p class="alert-info">Prices and discounts are set by the admin. Staff can see them but not change them.</p>
                <input type="hidden" id="discount_type" value="<?= e($discType) ?>"><input type="hidden" id="discount_value" value="<?= e($val('discount_value', '0')) ?>">
                <?php endif; ?>
                <div>
                    <label class="label" for="notes">Notes</label>
                    <textarea class="input" rows="3" id="notes" name="notes" placeholder="Stains, missing buttons, special care…"><?= e($val('notes')) ?></textarea>
                </div>
            </div>

            <div class="card card-pad">
                <h2 class="font-display text-lg font-bold">Summary</h2>
                <dl class="num mt-3 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">Pieces</dt><dd id="sum-pieces">0</dd></div>
                    <div class="<?= is_admin() ? '' : 'hidden' ?> flex justify-between"><dt class="text-muted">Subtotal</dt><dd id="sum-subtotal">₦0.00</dd></div>
                    <div class="<?= is_admin() ? '' : 'hidden' ?> flex justify-between"><dt class="text-muted">Discount</dt><dd id="sum-discount">−₦0.00</dd></div>
                    <div class="<?= is_admin() ? '' : 'hidden' ?> flex justify-between border-t border-line pt-3 text-base font-bold"><dt>Total</dt><dd id="sum-total">₦0.00</dd></div>
                </dl>
                <?php if (is_admin()): ?><p class="hint mt-3">Prices come from the service price list. You can overwrite any price above; the final amount is recalculated when you save.</p>
                <?php else: ?><p class="alert-info mt-3">No price is entered here. The admin sets the price after you submit the job.</p><?php endif; ?>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <button class="btn-primary" type="submit"><?= $isEdit && !$j['is_draft'] ? 'Save changes' : (is_admin() ? 'Save and create invoice' : 'Submit job') ?></button>
            <?php if (!$isEdit || $j['is_draft']): ?><button class="btn-ghost" type="submit" name="save_draft" value="1">Save as draft</button><?php endif; ?>
            <a class="btn-ghost" href="<?= url($isEdit ? '/jobs/' . $j['id'] : '/jobs') ?>">Cancel</a>
        </div>
    </form>
</div>
