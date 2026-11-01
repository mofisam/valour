<?php
$me = auth();
$S = fn($k, $d = '') => e(old($k, $settings[$k] ?? $d));
$nav = [['profile', 'Your profile'], ['password', 'Password']];
if (is_admin()) { $nav = array_merge($nav, [['business', 'Business details'], ['invoice', 'Invoices & payments']]); }
?>
<h1 class="mb-6 font-display text-2xl font-bold">Settings</h1>

<div class="grid gap-8 lg:grid-cols-[13rem_minmax(0,1fr)]">
    <nav class="lg:sticky lg:top-20 lg:self-start" aria-label="Settings sections">
        <ul class="flex gap-2 overflow-x-auto lg:flex-col lg:gap-1">
            <?php foreach ($nav as [$id, $label]): ?>
                <li><a class="block whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium text-fg2 hover:bg-canvas2" href="#<?= $id ?>"><?= e($label) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="space-y-6">
        <section id="profile" class="card card-pad scroll-mt-20">
            <h2 class="panel-title">Your profile</h2>
            <form method="post" action="<?= url('/settings/profile') ?>" class="mt-4 grid gap-4 sm:grid-cols-2">
                <?= csrf_field() ?>
                <div><label class="label" for="p_name">Name</label><input class="input" id="p_name" name="name" value="<?= e(old('name', $me['name'])) ?>" required></div>
                <div><label class="label" for="p_email">Email</label>
                    <input class="input <?= error('email') ? 'input-error' : '' ?>" type="email" id="p_email" name="email" value="<?= e(old('email', $me['email'])) ?>" required>
                    <?php if ($m = error('email')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?></div>
                <div class="sm:col-span-2"><button class="btn-primary" type="submit">Save profile</button></div>
            </form>
        </section>

        <section id="password" class="card card-pad scroll-mt-20">
            <h2 class="panel-title">Password</h2>
            <form method="post" action="<?= url('/settings/password') ?>" class="mt-4 grid gap-4 sm:grid-cols-3">
                <?= csrf_field() ?>
                <?php foreach ([['current_password', 'Current password', 'current-password'], ['password', 'New password', 'new-password'], ['password_confirmation', 'Confirm new password', 'new-password']] as [$n, $l, $ac]): ?>
                    <div><label class="label" for="pw_<?= $n ?>"><?= $l ?></label>
                        <input class="input <?= error($n) ? 'input-error' : '' ?>" type="password" id="pw_<?= $n ?>" name="<?= $n ?>" autocomplete="<?= $ac ?>" required>
                        <?php if ($m = error($n)): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?></div>
                <?php endforeach; ?>
                <div class="sm:col-span-3"><button class="btn-primary" type="submit">Change password</button></div>
            </form>
        </section>

        <?php if (is_admin()): ?>
        <form method="post" action="<?= url('/settings/business') ?>" enctype="multipart/form-data" class="space-y-6">
            <?= csrf_field() ?>
            <section id="business" class="card card-pad scroll-mt-20">
                <h2 class="panel-title">Business details</h2>
                <p class="text-sm text-muted">Your company name appears in the sidebar, login page, browser tab and on every invoice.</p>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="label" for="business_name">Company name</label>
                        <input class="input <?= error('business_name') ? 'input-error' : '' ?>" id="business_name" name="business_name" value="<?= $S('business_name') ?>" placeholder="e.g. Valour Empire Laundry Services" required>
                        <?php if ($m = error('business_name')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?>
                    </div>
                    <div class="md:col-span-2">
                        <label class="label" for="business_tagline">Login page headline (optional)</label>
                        <input class="input" id="business_tagline" name="business_tagline" maxlength="120" value="<?= $S('business_tagline') ?>" placeholder="Every garment, tracked from drop-off to pickup.">
                    </div>
                    <div><label class="label" for="business_phone">Phone</label><input class="input" id="business_phone" name="business_phone" value="<?= $S('business_phone') ?>"></div>
                    <div><label class="label" for="business_email">Email</label>
                        <input class="input <?= error('business_email') ? 'input-error' : '' ?>" type="email" id="business_email" name="business_email" value="<?= $S('business_email') ?>">
                        <?php if ($m = error('business_email')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?></div>
                    <div class="md:col-span-2"><label class="label" for="business_address">Address</label><textarea class="input" rows="2" id="business_address" name="business_address"><?= $S('business_address') ?></textarea></div>
                    <div class="md:col-span-2">
                        <label class="label" for="business_logo">Logo</label>
                        <div class="flex items-center gap-4">
                            <?php if (!empty($settings['business_logo_url'])): ?><img class="h-14 w-14 rounded border border-line bg-white object-contain p-1" src="<?= url($settings['business_logo_url']) ?>" alt="Current logo">
                            <?php else: ?><span class="flex h-14 w-14 items-center justify-center rounded bg-brass font-display text-xl font-bold text-ink"><?= e(brand_initial()) ?></span><?php endif; ?>
                            <div class="flex-1"><input class="input" type="file" id="business_logo" name="business_logo" accept="image/png,image/jpeg,image/webp">
                                <p class="hint">PNG, JPG or WebP, up to 2 MB. Until you upload one, the first letter of your company name is used.</p></div>
                        </div>
                        <?php if ($m = error('business_logo')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?>
                    </div>
                </div>
            </section>

            <section id="invoice" class="card card-pad scroll-mt-20">
                <h2 class="panel-title">Invoices &amp; payments</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div><label class="label" for="bank_name">Bank</label><input class="input" id="bank_name" name="bank_name" value="<?= $S('bank_name') ?>"></div>
                    <div><label class="label" for="bank_account_number">Account number</label><input class="input" id="bank_account_number" name="bank_account_number" inputmode="numeric" value="<?= $S('bank_account_number') ?>"></div>
                    <div class="md:col-span-2"><label class="label" for="bank_account_name">Account name</label><input class="input" id="bank_account_name" name="bank_account_name" value="<?= $S('bank_account_name') ?>"></div>
                    <div><label class="label" for="invoice_template">Invoice style</label>
                        <select class="input" id="invoice_template" name="invoice_template">
                            <?php foreach (['classic' => 'Classic (black and white, prints cleanly)', 'modern' => 'Modern (coloured header)'] as $k => $l): ?><option value="<?= $k ?>" <?= ($settings['invoice_template'] ?? 'classic') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
                        </select></div>
                    <div><label class="label" for="invoice_prefix">Invoice prefix</label><input class="input" id="invoice_prefix" name="invoice_prefix" maxlength="10" value="<?= $S('invoice_prefix', 'VE') ?>"><p class="hint">Numbers look like <?= e(strtoupper((string) ($settings['invoice_prefix'] ?? 'VE')) ?: 'VE') ?>-<?= date('y-m') ?>-001 and restart each month.</p></div>
                    <div class="md:col-span-2"><label class="label" for="invoice_footer">Invoice footer</label><textarea class="input" rows="2" id="invoice_footer" name="invoice_footer"><?= $S('invoice_footer') ?></textarea></div>
                </div>
            </section>

            <div><button class="btn-primary" type="submit">Save business settings</button></div>
        </form>

        <?php endif; ?>
    </div>
</div>
