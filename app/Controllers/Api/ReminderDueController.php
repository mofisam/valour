<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\{Auth, Controller};
use App\Models\{Notification, Reminder};

/** One poll for everything the admin should be told about: due reminders + unread notifications. */
class ReminderDueController extends Controller
{
    public function index(): void
    {
        $uid = (int) Auth::id();

        $due = array_map(fn($r) => [
            'id'    => (int) $r['id'],
            'title' => $r['title'],
            'notes' => $r['notes'],
            'at'    => date('D j M, g:i A', strtotime($r['remind_at'])),
        ], Reminder::due($uid));

        $notes = array_map(fn($n) => [
            'id'    => (int) $n['id'],
            'title' => $n['title'],
            'body'  => $n['body'],
            'link'  => $n['link'] ? url($n['link']) : null,
            'at'    => date('D j M, g:i A', strtotime($n['created_at'])),
        ], Notification::unread($uid));

        $this->json([
            'reminders'     => count($due),
            'notifications' => Notification::unreadCount($uid),
            'count'         => count($due) + Notification::unreadCount($uid),
            'due'           => $due,
            'notes'         => $notes,
        ]);
    }
}
