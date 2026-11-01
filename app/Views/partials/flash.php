<?php foreach (\App\Core\Session::messages() as $m): ?>
    <div class="alert-<?= e($m['type']) ?> mb-4" role="status"><?= e($m['message']) ?></div>
<?php endforeach; ?>
<?php if (\App\Core\Session::hasErrors() && error('login') === null): ?>
    <div class="alert-error mb-4" role="alert">Some details need fixing. Check the highlighted fields below.</div>
<?php endif; ?>
