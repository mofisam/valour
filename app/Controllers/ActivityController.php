<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Controller, Paginator};
use App\Models\{ActivityLog, User};

/** Admin-only audit trail: who did what, and when. */
class ActivityController extends Controller
{
    public function index(): void
    {
        $filters = $this->request->only(['user_id', 'entity', 'from', 'to']);
        $pager = new Paginator(ActivityLog::count($filters), (int) $this->request->input('page', 1), 30);

        $this->view('activity/index', [
            'title'    => 'Activity log',
            'logs'     => ActivityLog::paginate($filters, $pager->perPage, $pager->offset),
            'pager'    => $pager,
            'filters'  => $filters,
            'users'    => User::listAll(),
            'entities' => ActivityLog::entities(),
        ]);
    }
}
