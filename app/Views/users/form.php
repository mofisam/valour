<?php $u = $user; ?>
<div class="mx-auto max-w-xl">
    <h1 class="mb-5 font-display text-2xl font-bold"><?= $u ? 'Edit account' : 'New staff account' ?></h1>
    <form method="post" action="<?= url($u ? '/users/' . $u['id'] : '/users') ?>" class="card card-pad space-y-4">
        <?= csrf_field() ?>
        <div><label class="label" for="name">Full name</label>
            <input class="input <?= error('name') ? 'input-error' : '' ?>" id="name" name="name" value="<?= e(old('name', $u['name'] ?? '')) ?>" required>
            <?php if ($m = error('name')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?></div>
        <div><label class="label" for="email">Email</label>
            <input class="input <?= error('email') ? 'input-error' : '' ?>" type="email" id="email" name="email" value="<?= e(old('email', $u['email'] ?? '')) ?>" required>
            <?php if ($m = error('email')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?></div>
        <div><label class="label" for="role_id">Role</label>
            <select class="input" id="role_id" name="role_id">
                <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>" <?= (string) old('role_id', $u['role_id'] ?? 2) === (string) $r['id'] ? 'selected' : '' ?>><?= e($r['label']) ?></option><?php endforeach; ?>
            </select>
            <?php if ($m = error('role_id')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?>
            <p class="hint">Staff can manage jobs, customers and expenses. Administrators also see reports, prices, staff and settings.</p></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="password"><?= $u ? 'New password' : 'Password' ?></label>
                <input class="input <?= error('password') ? 'input-error' : '' ?>" type="password" id="password" name="password" autocomplete="new-password" <?= $u ? '' : 'required' ?>>
                <?php if ($m = error('password')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?>
                <?php if ($u): ?><p class="hint">Leave blank to keep the current password.</p><?php endif; ?></div>
            <div><label class="label" for="password_confirmation">Confirm password</label>
                <input class="input <?= error('password_confirmation') ? 'input-error' : '' ?>" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                <?php if ($m = error('password_confirmation')): ?><p class="field-error"><?= e($m) ?></p><?php endif; ?></div>
        </div>
        <?php if ($u): ?>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" <?= $u['is_active'] ? 'checked' : '' ?>> This account can sign in</label>
        <?php endif; ?>
        <div class="flex gap-3"><button class="btn-primary" type="submit"><?= $u ? 'Save changes' : 'Create account' ?></button><a class="btn-ghost" href="<?= url('/users') ?>">Cancel</a></div>
    </form>
</div>
