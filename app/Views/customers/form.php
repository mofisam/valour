<?php $c = $customer; $action = $c ? url('/customers/' . $c['id']) : url('/customers'); ?>
<div class="mx-auto max-w-2xl">
    <h1 class="mb-5 font-display text-2xl font-bold"><?= $c ? 'Edit customer' : 'New customer' ?></h1>
    <form method="post" action="<?= $action ?>" class="card card-pad space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="label" for="full_name">Full name</label>
            <input class="input <?= error('full_name') ? 'input-error' : '' ?>" id="full_name" name="full_name" value="<?= e(old('full_name', $c['full_name'] ?? '')) ?>" required>
            <?php if ($m = error('full_name')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="phone_number">Phone number</label>
                <input class="input" id="phone_number" name="phone_number" inputmode="tel" value="<?= e(old('phone_number', $c['phone_number'] ?? '')) ?>">
            </div>
            <div>
                <label class="label" for="email">Email (optional)</label>
                <input class="input <?= error('email') ? 'input-error' : '' ?>" type="email" id="email" name="email" value="<?= e(old('email', $c['email'] ?? '')) ?>">
                <?php if ($m = error('email')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?>
            </div>
        </div>
        <div>
            <label class="label" for="birthday">Birthday</label>
            <input class="input sm:max-w-xs" type="date" id="birthday" name="birthday" value="<?= e(old('birthday', $c['birthday'] ?? '')) ?>">
            <p class="hint">Used for the birthday reminders on your dashboard.</p>
        </div>
        <div>
            <label class="label" for="address">Address</label>
            <textarea class="input" rows="2" id="address" name="address"><?= e(old('address', $c['address'] ?? '')) ?></textarea>
        </div>
        <div>
            <label class="label" for="notes">Notes</label>
            <textarea class="input" rows="2" id="notes" name="notes" placeholder="Starch preferences, delivery landmarks…"><?= e(old('notes', $c['notes'] ?? '')) ?></textarea>
        </div>
        <div class="flex flex-wrap gap-3 pt-2">
            <button class="btn-primary" type="submit">Save customer</button>
            <?php if (!$c): ?><button class="btn-accent" type="submit" name="then" value="job">Save and create job</button><?php endif; ?>
            <a class="btn-ghost" href="<?= url($c ? '/customers/' . $c['id'] : '/customers') ?>">Cancel</a>
        </div>
    </form>
</div>
