<h1 class="font-display text-2xl font-bold">You can't open this</h1>
<p class="mt-2 text-sm text-muted"><?= e($message ?: 'Your account does not have permission for this page.') ?></p>
<a href="<?= url('/') ?>" class="btn-primary mt-5">Back to dashboard</a>
