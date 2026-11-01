<?php /* Template cloned by jobs.js. __i__ is replaced with a running index. */
use App\Services\PricingService;
$a = is_admin(); // staff never see or type prices ?>
<div data-row class="grid grid-cols-2 gap-3 rounded-md border border-line p-3 sm:grid-cols-12 sm:items-start">
    <div class="col-span-2 <?= $a ? 'sm:col-span-3' : 'sm:col-span-4' ?>">
        <label class="label">Item</label>
        <select class="input" name="items[__i__][clothing_type_id]" data-f="type"><option value="">Choose…</option></select>
    </div>
    <div class="<?= $a ? 'sm:col-span-2' : 'sm:col-span-3' ?>">
        <label class="label">Colour</label>
        <select class="input" name="items[__i__][color]" data-f="color">
            <?php foreach (PricingService::COLOURS as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="sm:col-span-2">
        <label class="label">Length</label>
        <select class="input" name="items[__i__][length]" data-f="length">
            <?php foreach (PricingService::LENGTHS as $k => $l): ?><option value="<?= $k ?>" <?= $k === 'nil' ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="sm:col-span-1">
        <label class="label">Qty</label>
        <input class="input" type="number" min="1" step="1" name="items[__i__][quantity]" data-f="qty" value="1">
    </div>
    <div class="sm:col-span-2 <?= $a ? '' : 'hidden' ?>" data-price-col>
        <label class="label">Price each</label>
        <input class="input" type="number" min="0" step="0.01" name="items[__i__][price_per_unit]" data-f="price">
    </div>
    <div class="col-span-2 flex items-start justify-between gap-2 sm:col-span-2 sm:block sm:text-right" >
        <div data-price-col class="<?= $a ? '' : 'hidden' ?>">
            <div class="label">Amount</div>
            <div class="num py-2 text-sm font-semibold" data-f="line"></div>
        </div>
        <button type="button" class="btn-danger btn-sm mt-1" data-remove aria-label="Remove item">Remove</button>
    </div>
</div>
