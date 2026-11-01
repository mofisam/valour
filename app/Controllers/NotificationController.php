<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Controller};
use App\Models\{Notification, Reminder};

/** Admin-only in-app notifications. */
class NotificationController extends Controller
{
    public function index(): void
    {
        $uid = (int) Auth::id();
        $this->view('notifications/index', [
            'title'         => 'Notifications',
            'notifications' => Notification::recent($uid),
            'dueReminders'  => Reminder::due($uid),
        ]);
    }

    public function read(string $id): void
    {
        Notification::markRead((int) $id, (int) Auth::id());
        $this->respond('Marked as read.');
    }

    public function readAll(): void
    {
        Notification::markAllRead((int) Auth::id());
        $this->respond('All notifications marked as read.');
    }

    private function respond(string $message): never
    {
        if ($this->request->isAjax()) {
            $this->json(['ok' => true]);
        }
        $this->flash('success', $message);
        $this->redirect('/notifications');
    }
}
