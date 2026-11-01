<?php /** @var App\Core\Paginator $pager */ if ($pager->pages > 1): ?>
<nav class="flex items-center justify-between border-t border-line px-4 py-3 text-sm" aria-label="Pagination">
    <span class="text-muted">Page <?= $pager->page ?> of <?= $pager->pages ?> · <?= number_format($pager->total) ?> results</span>
    <div class="flex gap-2">
        <?php if ($pager->page > 1): ?><a class="btn-ghost btn-sm" href="<?= e($pager->link($pager->page - 1)) ?>">Previous</a><?php endif; ?>
        <?php if ($pager->page < $pager->pages): ?><a class="btn-ghost btn-sm" href="<?= e($pager->link($pager->page + 1)) ?>">Next</a><?php endif; ?>
    </div>
</nav>
<?php endif; ?>
