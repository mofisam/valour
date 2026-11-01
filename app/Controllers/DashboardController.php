<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Controller};
use App\Models\{Customer, Reminder};
use App\Services\ReportService;

class DashboardController extends Controller
{
    public function index(): void
    {
        $admin = is_admin();
        $uid = (int) Auth::id();

        $this->view('dashboard/index', [
            'title'     => 'Dashboard',
            'stats'     => ReportService::dashboard(),
            'recent'    => ReportService::recentJobs(8),
            'birthdays' => Customer::birthdaysThisMonth(),
            'attention' => ReportService::attention(),
            'insights'  => $admin ? ReportService::adminInsights() : null,
            'debtors'   => $admin ? Customer::topDebtors(5) : [],
            'owed'      => $admin ? Customer::owingSummary() : null,
            'dueReminders'      => $admin ? Reminder::due($uid) : [],
            'upcomingReminders' => $admin ? array_slice(Reminder::upcoming($uid), 0, 3) : [],
        ]);
    }
}
