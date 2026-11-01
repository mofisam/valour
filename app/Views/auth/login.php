<h2 class="font-display text-3xl font-bold">Welcome back</h2>
<p class="mt-2 mb-7 text-sm text-muted">Sign in with the account your administrator gave you.</p>

<?php if ($err = error('login')): ?>
    <div class="alert-error mb-5 flex gap-2" role="alert">
        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        <span><?= e($err) ?></span>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('/login') ?>" class="space-y-5">
    <?= csrf_field() ?>
    <div>
        <label class="label" for="email">Email address</label>
        <input class="input py-2.5" type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="username" placeholder="you@example.com" required <?= old('email') ? '' : 'autofocus' ?>>
    </div>
    <div>
        <label class="label" for="password">Password</label>
        <div class="relative">
            <input class="input py-2.5 pr-16" type="password" id="password" name="password" autocomplete="current-password" required <?= old('email') ? 'autofocus' : '' ?>>
            <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-link hover:underline" aria-controls="password" aria-pressed="false">Show</button>
        </div>
        <p id="caps-warning" class="field-error hidden">Caps Lock is on.</p>
    </div>
    <button class="btn-primary w-full py-2.5" type="submit">Sign in</button>
    <p class="text-center text-xs text-muted">Forgot your password? Ask your administrator to reset it from Staff accounts.</p>
</form>

<script>
    (function () {
        var pw = document.getElementById('password'), btn = document.getElementById('toggle-password'), caps = document.getElementById('caps-warning');
        btn.addEventListener('click', function () {
            var show = pw.type === 'password';
            pw.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Hide' : 'Show';
            btn.setAttribute('aria-pressed', show);
        });
        pw.addEventListener('keyup', function (e) { caps.classList.toggle('hidden', !(e.getModifierState && e.getModifierState('CapsLock'))); });
    })();
</script>
