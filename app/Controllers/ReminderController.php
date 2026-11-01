<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Controller, Session, Validator};
use App\Models\{ActivityLog, Reminder};

/** Admin-only personal reminders. */
class ReminderController extends Controller
{
    public function index(): void
    {
        $uid = (int) Auth::id();
        $this->view('reminders/index', [
            'title'     => 'Reminders',
            'due'       => Reminder::due($uid),
            'upcoming'  => Reminder::upcoming($uid),
            'completed' => Reminder::completed($uid),
        ]);
    }

    public function store(): void
    {
        $in = $this->request->all();
        $errors = Validator::validate($in, [
            'title'       => 'required|max_length:255',
            'remind_at'   => 'required',
            'repeat_rule' => 'in:none,daily,weekly,monthly',
        ], ['remind_at' => 'Date and time', 'repeat_rule' => 'Repeat']);

        $when = !isset($errors['remind_at']) ? date_create_from_format('Y-m-d\TH:i', (string) $in['remind_at']) : false;
        if (!isset($errors['remind_at']) && !$when) {
            $errors['remind_at'] = 'Choose a valid date and time.';
        }
        if ($errors) {
            Session::flashErrors($errors, $in);
            $this->redirect('/reminders');
        }

        $rid = Reminder::insert([
            'user_id'     => Auth::id(),
            'title'       => $in['title'],
            'notes'       => ($in['notes'] ?? '') !== '' ? $in['notes'] : null,
            'remind_at'   => $when->format('Y-m-d H:i:00'),
            'repeat_rule' => $in['repeat_rule'] ?? 'none',
        ]);
        ActivityLog::log('reminder.created', 'reminder', $rid, $in['title'] . ' at ' . $when->format('D j M, g:i A') . ($in['repeat_rule'] !== 'none' ? ', repeats ' . $in['repeat_rule'] : ''));
        $this->flash('success', 'Reminder set for ' . $when->format('D j M, g:i A') . '.');
        $this->redirect('/reminders');
    }

    public function done(string $id): void
    {
        $r = Reminder::owned((int) $id, (int) Auth::id()) ?? abort(404);
        Reminder::complete($r);
        ActivityLog::log('reminder.done', 'reminder', (int) $id, $r['title']);
        $this->respond($r['repeat_rule'] === 'none' ? 'Reminder completed.' : 'Done. Next reminder scheduled.');
    }

    public function snooze(string $id): void
    {
        Reminder::owned((int) $id, (int) Auth::id()) ?? abort(404);
        $when = (string) $this->request->input('when', '10m');

        $until = match ($when) {
            '1h'       => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'tomorrow' => date('Y-m-d 08:00:00', strtotime('tomorrow')),
            default    => date('Y-m-d H:i:s', strtotime('+10 minutes')),
        };
        Reminder::snooze((int) $id, $until);
        $this->respond('Snoozed until ' . date('D j M, g:i A', strtotime($until)) . '.');
    }

    public function destroy(string $id): void
    {
        Reminder::owned((int) $id, (int) Auth::id()) ?? abort(404);
        $r = Reminder::owned((int) $id, (int) Auth::id());
        Reminder::delete((int) $id);
        ActivityLog::log('reminder.deleted', 'reminder', (int) $id, $r['title'] ?? '');
        $this->respond('Reminder deleted.');
    }

    /** JSON for the background poller; redirect for normal form posts. */
    private function respond(string $message): never
    {
        if ($this->request->isAjax()) {
            $this->json(['ok' => true, 'message' => $message, 'count' => Reminder::dueCount((int) Auth::id())]);
        }
        $this->flash('success', $message);
        $this->redirect('/reminders');
    }
}
