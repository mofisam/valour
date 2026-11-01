<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\{Auth, Request};

class RoleMiddleware
{
    public function __construct(private array $roles) {}

    public function handle(Request $request): void
    {
        if (!Auth::is(...$this->roles)) {
            abort(403, 'You do not have permission to open this page.');
        }
    }
}
