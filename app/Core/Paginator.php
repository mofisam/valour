<?php
declare(strict_types=1);

namespace App\Core;

class Paginator
{
    public int $pages;
    public int $page;
    public int $offset;

    public function __construct(public int $total, int $page, public int $perPage = 15)
    {
        $this->pages  = max(1, (int) ceil($total / $perPage));
        $this->page   = min(max(1, $page), $this->pages);
        $this->offset = ($this->page - 1) * $perPage;
    }

    /** Build a URL for a page, keeping current query string filters. */
    public function link(int $page): string
    {
        $q = $_GET;
        $q['page'] = $page;
        return url(current_path()) . '?' . http_build_query($q);
    }
}
