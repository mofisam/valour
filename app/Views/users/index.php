<div class="mb-5 flex items-center justify-between">
    <h1 class="font-display text-2xl font-bold">Staff accounts</h1>
    <a class="btn-primary" href="<?= url('/users/create') ?>">Add staff</a>
</div>
<div class="card table-wrap">
    <table class="table">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last sign-in</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td class="font-medium"><?= e($u['name']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= e($u['role_label']) ?></td>
                <td><?= $u['is_active'] ? status_badge('paid') : status_badge('cancelled') ?></td>
                <td><?= e(fdate($u['last_login_at'], 'd M Y, H:i')) ?></td>
                <td class="text-right"><a class="btn-ghost btn-sm" href="<?= url('/users/' . $u['id'] . '/edit') ?>">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
