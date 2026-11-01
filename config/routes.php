<?php
/** @var App\Core\Router $router */

use App\Controllers\{AuthController, DashboardController, CustomerController, JobController, InvoiceController,
    PaymentController, CashController, NotificationController, ExpenseController, ExpenseCategoryController, ActivityController, ReceivablesController, ReminderController, ServiceController, ReportController, UserController, SettingsController};
use App\Controllers\Api\{CustomerSearchController, PricingController, ReminderDueController};

// ---- Public ----
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/session-expired', [AuthController::class, 'expired']);

// ---- Logged-in users (admin + staff) ----
$router->group(['auth'], function ($r) {
    $r->post('/logout', [AuthController::class, 'logout']);
    $r->get('/', [DashboardController::class, 'index']);

    // Customers (route order matters: /create before /{id})
    $r->get('/customers', [CustomerController::class, 'index'], ['role:admin']);
    // Customer pages are for admins. Staff only pick an existing customer in the job form's search box.
    $r->get('/customers/create', [CustomerController::class, 'create'], ['role:admin']);
    $r->post('/customers', [CustomerController::class, 'store'], ['role:admin']);
    $r->get('/customers/{id}', [CustomerController::class, 'show'], ['role:admin']);
    $r->get('/customers/{id}/edit', [CustomerController::class, 'edit'], ['role:admin']);
    $r->post('/customers/{id}', [CustomerController::class, 'update'], ['role:admin']);
    $r->post('/customers/{id}/delete', [CustomerController::class, 'destroy'], ['role:admin']);

    // Jobs
    $r->get('/jobs', [JobController::class, 'index']);
    $r->get('/jobs/create', [JobController::class, 'create']);
    $r->post('/jobs', [JobController::class, 'store']);
    $r->get('/jobs/{id}', [JobController::class, 'show']);
    $r->get('/jobs/{id}/edit', [JobController::class, 'edit']);
    $r->post('/jobs/{id}', [JobController::class, 'update']);
    $r->post('/jobs/{id}/status', [JobController::class, 'status']);
    $r->post('/jobs/{id}/finalize', [JobController::class, 'finalize']);
    $r->post('/jobs/{id}/delete', [JobController::class, 'destroy'], ['role:admin']);

    // Payments
    $r->post('/jobs/{id}/payments', [PaymentController::class, 'store'], ['role:admin']);
    $r->post('/jobs/{id}/price', [JobController::class, 'price'], ['role:admin']);
    $r->post('/payments/{id}/delete', [PaymentController::class, 'destroy'], ['role:admin']);

    // Invoices
    $r->get('/invoices/{id}', [InvoiceController::class, 'show']);
    $r->get('/invoices/{id}/pdf', [InvoiceController::class, 'pdf']);

    // Expenses
    // Expenses (admin only)
    $r->get('/expenses', [ExpenseController::class, 'index'], ['role:admin']);
    $r->post('/expenses', [ExpenseController::class, 'store'], ['role:admin']);
    $r->get('/expenses/{id}/edit', [ExpenseController::class, 'edit'], ['role:admin']);
    $r->post('/expenses/{id}', [ExpenseController::class, 'update'], ['role:admin']);
    $r->post('/expenses/{id}/delete', [ExpenseController::class, 'destroy'], ['role:admin']);

    // Own profile / password
    $r->get('/settings', [SettingsController::class, 'index']);
    $r->post('/settings/profile', [SettingsController::class, 'updateProfile']);
    $r->post('/settings/password', [SettingsController::class, 'updatePassword']);

    // JSON endpoints used by the browser JS
    $r->get('/api/customers/search', [CustomerSearchController::class, 'index']);
    $r->get('/api/pricing', [PricingController::class, 'index']);
    $r->get('/api/ping', [AuthController::class, 'ping']);
});

// ---- Admin only ----
$router->group(['auth', 'role:admin'], function ($r) {
    $r->get('/services', [ServiceController::class, 'index']);
    $r->post('/services/prices', [ServiceController::class, 'savePrices']);
    $r->post('/services', [ServiceController::class, 'saveService']);
    $r->post('/services/{id}', [ServiceController::class, 'saveService']);
    $r->post('/clothing-types', [ServiceController::class, 'saveClothingType']);
    $r->post('/clothing-types/{id}', [ServiceController::class, 'saveClothingType']);

    $r->get('/cash', [CashController::class, 'index']);
    $r->get('/receivables', [ReceivablesController::class, 'index']);
    $r->get('/activity', [ActivityController::class, 'index']);

    $r->get('/expense-categories', [ExpenseCategoryController::class, 'index']);
    $r->post('/expense-categories', [ExpenseCategoryController::class, 'save']);
    $r->post('/expense-categories/{id}', [ExpenseCategoryController::class, 'save']);
    $r->post('/expense-categories/{id}/delete', [ExpenseCategoryController::class, 'destroy']);

    $r->get('/reports', [ReportController::class, 'index']);
    $r->get('/reports/export', [ReportController::class, 'export']);

    $r->get('/users', [UserController::class, 'index']);
    $r->get('/users/create', [UserController::class, 'create']);
    $r->post('/users', [UserController::class, 'store']);
    $r->get('/users/{id}/edit', [UserController::class, 'edit']);
    $r->post('/users/{id}', [UserController::class, 'update']);

    $r->post('/settings/business', [SettingsController::class, 'updateBusiness']);

    // Notifications (e.g. "job needs a price")
    $r->get('/notifications', [NotificationController::class, 'index'], ['role:admin']);
    $r->post('/notifications/read-all', [NotificationController::class, 'readAll'], ['role:admin']);
    $r->post('/notifications/{id}/read', [NotificationController::class, 'read'], ['role:admin']);

    // Reminders (personal to each admin)
    $r->get('/reminders', [ReminderController::class, 'index']);
    $r->post('/reminders', [ReminderController::class, 'store']);
    $r->post('/reminders/{id}/done', [ReminderController::class, 'done']);
    $r->post('/reminders/{id}/snooze', [ReminderController::class, 'snooze']);
    $r->post('/reminders/{id}/delete', [ReminderController::class, 'destroy']);
    $r->get('/api/reminders/due', [ReminderDueController::class, 'index']);
});
