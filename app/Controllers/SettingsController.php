<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Controller, Session, Validator};
use App\Models\{ActivityLog, Setting, User};
use App\Services\UploadService;

class SettingsController extends Controller
{
    private const BUSINESS_KEYS = [
        'business_name', 'business_tagline', 'business_address', 'business_phone', 'business_email',
        'bank_name', 'bank_account_number', 'bank_account_name',
        'invoice_template', 'invoice_prefix', 'invoice_footer',
    ];

    public function index(): void
    {
        $this->view('settings/index', [
            'title'    => 'Settings',
            'settings' => Setting::all_(),
        ]);
    }

    public function updateProfile(): void
    {
        $in = $this->request->all();
        $errors = Validator::validate($in, ['name' => 'required|max_length:255', 'email' => 'required|email']);
        if (!isset($errors['email']) && User::emailTaken($in['email'], Auth::id())) {
            $errors['email'] = 'That email is already in use.';
        }
        if ($errors) {
            Session::flashErrors($errors, $in);
            $this->redirect('/settings');
        }
        User::update(Auth::id(), ['name' => $in['name'], 'email' => strtolower($in['email'])]);
        ActivityLog::log('profile.updated', 'user', Auth::id(), $in['name'] . ' (' . strtolower($in['email']) . ')');
        $this->flash('success', 'Profile updated.');
        $this->redirect('/settings');
    }

    public function updatePassword(): void
    {
        $in = $this->request->all();
        $errors = Validator::validate($in, [
            'current_password'      => 'required',
            'password'              => 'required|min_length:8',
            'password_confirmation' => 'required|same:password',
        ], ['current_password' => 'Current password', 'password_confirmation' => 'Password confirmation']);

        $me = User::find(Auth::id());
        if (!isset($errors['current_password']) && !password_verify($in['current_password'], $me['password_hash'])) {
            $errors['current_password'] = 'Current password is incorrect.';
        }
        if ($errors) {
            Session::flashErrors($errors);
            $this->redirect('/settings');
        }
        User::update(Auth::id(), ['password_hash' => password_hash($in['password'], PASSWORD_DEFAULT)]);
        Session::regenerate();
        ActivityLog::log('password.changed', 'user', Auth::id(), (Auth::user()['name'] ?? '') . ' changed their own password');
        $this->flash('success', 'Password changed.');
        $this->redirect('/settings');
    }

    public function updateBusiness(): void
    {
        $in = $this->request->all();
        $errors = Validator::validate($in, [
            'business_name'    => 'required|max_length:255',
            'business_email'   => 'email',
            'invoice_template' => 'required|in:classic,modern',
            'invoice_prefix'   => 'max_length:10',
        ]);
        if ($errors) {
            Session::flashErrors($errors, $in);
            $this->redirect('/settings');
        }

        if ($file = $this->request->file('business_logo')) {
            [$path, $err] = UploadService::image($file, 'logo');
            if ($err) {
                Session::flashErrors(['business_logo' => $err], $in);
                $this->redirect('/settings');
            }
            Setting::set('business_logo_url', $path);
        }

        foreach (self::BUSINESS_KEYS as $key) {
            Setting::set($key, $in[$key] ?? '');
        }
        ActivityLog::log('settings.updated', 'settings', null, 'Business settings saved for ' . ($in['business_name'] ?? '') . ' (invoice style: ' . ($in['invoice_template'] ?? '') . ')');
        $this->flash('success', 'Business settings saved.');
        $this->redirect('/settings');
    }

    /** Admin: remove old (or all) activity-log entries. One entry is written afterwards so the clean-up itself is on record. */
    public function clearActivity(): void
    {
        $range = (string) $this->request->input('range', '');
        $days = ['30' => 30, '90' => 90, '180' => 180, '365' => 365][$range] ?? null;
        if ($range !== 'all' && $days === null) {
            $this->flash('error', 'Choose how much of the activity log to clear.');
            $this->redirect('/settings#activity');
        }
        $removed = ActivityLog::clear($range === 'all' ? null : $days);
        ActivityLog::log('activity.cleared', 'settings', null, "$removed entr" . ($removed === 1 ? 'y' : 'ies') . ' removed (' . ($range === 'all' ? 'everything' : "older than $days days") . ')');
        $this->flash('success', $removed . ' activity-log entr' . ($removed === 1 ? 'y' : 'ies') . ' cleared.');
        $this->redirect('/settings#activity');
    }
}
